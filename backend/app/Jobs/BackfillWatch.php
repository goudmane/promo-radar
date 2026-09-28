<?php
namespace App\Jobs;
use App\Models\Offer;
use App\Models\OfferAlert;
use App\Models\Watch;
use App\Services\WatchMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class BackfillWatch implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $watchId) {}
    public function handle(WatchMatcher $matcher): void {
        $watch=Watch::find($this->watchId);
        if (!$watch || $watch->muted) return;
        Offer::with('product')->where('active',true)->chunkById(200,function ($offers) use ($watch,$matcher) {
            foreach ($offers as $offer) if ($matcher->matches($watch,$offer)) {
                $alert=OfferAlert::firstOrCreate(
                    ['watch_id'=>$watch->id,'offer_id'=>$offer->id,'revision'=>$offer->revision],
                    ['user_id'=>$watch->user_id]);
                if ($alert->wasRecentlyCreated) SendOfferMail::dispatch($alert->id);
            }
        });
    }
}
