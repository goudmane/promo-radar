<?php
namespace App\Services;
use App\Models\Offer;
use App\Models\Watch;
use Illuminate\Support\Str;
class WatchMatcher {
    public function matches(Watch $w,Offer $o): bool {
        if ($w->muted || !$o->active || ($o->valid_until && $o->valid_until->isPast())
            || ($o->valid_from && $o->valid_from->isFuture())) return false;
        $text=Str::lower(implode(' ',array_filter([$o->title,$o->description,
            $o->product?->name,$o->product?->brand,$o->product?->model,$o->product?->category])));
        foreach ($w->include_terms as $word) if (!str_contains($text,Str::lower($word))) return false;
        foreach ($w->exclude_terms??[] as $word) if (str_contains($text,Str::lower($word))) return false;
        if ($w->brand && !str_contains(Str::lower($o->product?->brand??''),Str::lower($w->brand))) return false;
        if ($w->category && !str_contains(Str::lower($o->product?->category??''),Str::lower($w->category))) return false;
        if ($w->max_price_minor!==null && $o->price_minor>$w->max_price_minor) return false;
        if ($w->min_discount!==null && $o->discount_percent<$w->min_discount) return false;
        foreach (['retailers'=>'retailer','cities'=>'city'] as $key=>$field) {
            if ($w->$key && !in_array(Str::lower($o->$field??''),array_map(Str::lower(...),$w->$key),true)) return false;
        }
        return !$w->channels || in_array($o->channel,$w->channels,true) || $o->channel==='both';
    }
}
