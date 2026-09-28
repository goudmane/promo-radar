<?php
namespace App\Jobs;
use App\Mail\OfferMatchMail;
use App\Models\OfferAlert;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
class DailyDigest implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $userId) {}
    public function handle(): void {
        if (!config('promo.email_enabled')) return;
        $lock=Cache::lock('digest:'.$this->userId,60);
        if (!$lock->get()) return;
        try {
            $user=User::find($this->userId);
            if (!$user || $user->email_mode!=='digest') return;
            $alerts=OfferAlert::with('offer')->where('user_id',$user->id)->whereNull('emailed_at')
                ->where('created_at','<=',now()->subDay())->orderBy('id')->limit(100)->get();
            if ($alerts->isEmpty()) return;
            Mail::to($user->email)->send(new OfferMatchMail($alerts->all()));
            OfferAlert::whereIn('id',$alerts->pluck('id'))->update(['emailed_at'=>now()]);
            if ($alerts->count()===100) self::dispatch($user->id)->delay(now()->addMinute());
        } finally { $lock->release(); }
    }
}
