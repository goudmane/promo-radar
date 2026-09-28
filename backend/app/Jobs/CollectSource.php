<?php
namespace App\Jobs;
use App\Models\Source;
use App\Services\OfferIngestor;
use App\Services\SourceUrlPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CollectSource implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout=180;
    public function __construct(public int $sourceId,public bool $force=false) {}
    public function handle(OfferIngestor $ingestor,SourceUrlPolicy $policy): void {
        $lock=Cache::lock('source:'.$this->sourceId,240);
        if (!$lock->get()) return;
        try {
            $source=Source::find($this->sourceId);
            if (!$source || (!$source->enabled && !$this->force)) return;
            $source->update(['next_run_at'=>now()->addMinutes($source->interval_minutes)]);
            $runId=DB::table('source_runs')->insertGetId(['source_id'=>$source->id,'status'=>'running','started_at'=>now()]);
            try {
                $policy->assertAllowed($source->url,$source->allowed_host);
                if ($source->driver==='json_feed') {
                    $response=Http::timeout(30)->withoutRedirecting()->get($source->url)->throw();
                    if (strlen($response->body())>5000000) throw new \RuntimeException('Feed exceeds 5 MB');
                    $payload=$response->json();
                    $rows=is_array($payload)?($payload['offers']??$payload):null;
                } elseif ($source->driver==='browser') {
                    $response=Http::timeout(150)->withToken(config('services.scraper.token'))
                        ->post(config('services.scraper.url').'/extract',[
                            'url'=>$source->url,'allowedHost'=>$source->allowed_host,
                            'profile'=>$source->mapping??[],
                        ])->throw();
                    $rows=$response->json('rows');
                } else throw new \RuntimeException('Unknown driver');
                if (!is_array($rows) || !array_is_list($rows) || count($rows)>5000) throw new \RuntimeException('Invalid rows payload');
                $totals=['received'=>0,'changed'=>0,'skipped'=>0];
                foreach (array_chunk($rows,500) as $batch) {
                    $result=$ingestor->ingest($source,$batch);
                    foreach ($totals as $key=>$value) $totals[$key]+=$result[$key];
                }
                $source->update(['last_run_at'=>now(),'last_error'=>null]);
                DB::table('source_runs')->where('id',$runId)->update(['status'=>'completed',
                    'items'=>$totals['received'],'changed'=>$totals['changed'],'finished_at'=>now()]);
            } catch (\Throwable $e) {
                $source->update(['last_error'=>mb_substr($e->getMessage(),0,1000)]);
                DB::table('source_runs')->where('id',$runId)->update(['status'=>'failed','error'=>mb_substr($e->getMessage(),0,1000),'finished_at'=>now()]);
                throw $e;
            }
        } finally { $lock->release(); }
    }
}
