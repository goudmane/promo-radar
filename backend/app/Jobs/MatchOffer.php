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
class MatchOffer implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $offerId,public string $revision) {}
    public function handle(WatchMatcher $matcher): void {
        $offer=Offer::with('product')->find($this->offerId);
        if (!$offer || $offer->revision!==$this->revision) return;
        Watch::where('muted',false)->chunkById(200,function ($watches) use ($matcher,$offer) {
            foreach ($watches as $watch) if ($matcher->matches($watch,$offer)) {
                $alert=OfferAlert::firstOrCreate(
                    ['watch_id'=>$watch->id,'offer_id'=>$offer->id,'revision'=>$offer->revision],
                    ['user_id'=>$watch->user_id]);
                if ($alert->wasRecentlyCreated) SendOfferMail::dispatch($alert->id);
            }
        });
    }
}
