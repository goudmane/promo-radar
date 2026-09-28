<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
class OfferMatchMail extends Mailable {
    public function __construct(public array $alerts) {}
    public function build() {
        return $this->subject('Promo Radar: '.count($this->alerts).' matching offer(s)')
            ->view('emails.offers');
    }
}
