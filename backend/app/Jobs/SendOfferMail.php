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
use Illuminate\Support\Facades\Mail;
class SendOfferMail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries=3;
    public function __construct(public int $alertId) {}
    public function handle(): void {
        $alert=OfferAlert::with('offer')->find($this->alertId);
        if (!$alert || $alert->emailed_at || !config('promo.email_enabled')) return;
        $user=User::find($alert->user_id);
        if (!$user || $user->email_mode==='off') return;
        if ($user->email_mode==='digest') return;
        Mail::to($user->email)->send(new OfferMatchMail([$alert]));
        $alert->update(['emailed_at'=>now()]);
    }
}
