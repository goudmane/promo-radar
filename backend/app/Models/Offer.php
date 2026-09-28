<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Offer extends Model {
    protected $guarded=[];
    protected function casts(): array { return ['active'=>'boolean','seen_at'=>'datetime','valid_from'=>'datetime','valid_until'=>'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function source() { return $this->belongsTo(Source::class); }
    public function getDiscountPercentAttribute(): float {
        return $this->original_price_minor > $this->price_minor
            ? round(100 * ($this->original_price_minor - $this->price_minor) / $this->original_price_minor, 1) : 0;
    }
}
