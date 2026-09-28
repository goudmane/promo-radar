<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OfferAlert extends Model {
    protected $guarded=[];
    protected function casts(): array { return ['read_at'=>'datetime','emailed_at'=>'datetime']; }
    public function offer() { return $this->belongsTo(Offer::class); }
    public function watch() { return $this->belongsTo(Watch::class); }
}
