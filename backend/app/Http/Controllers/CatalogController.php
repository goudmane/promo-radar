<?php
namespace App\Http\Controllers;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller {
    public function index(Request $r) {
        $v=$r->validate([
            'search'=>'nullable|string|max:200','channel'=>'nullable|in:online,store,both',
            'city'=>'nullable|string|max:100','retailer'=>'nullable|string|max:100',
            'brand'=>'nullable|string|max:100','category'=>'nullable|string|max:100',
            'min_price'=>'nullable|numeric|min:0','max_price'=>'nullable|numeric|min:0',
            'min_discount'=>'nullable|numeric|between:0,100','fresh_hours'=>'nullable|integer|between:1,720',
            'sort'=>'nullable|in:newest,price_asc,price_desc,discount','per_page'=>'nullable|integer|between:1,100',
        ]);
        $q=Offer::query()->with('product')->where('active',true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from','<=',now()))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until','>=',now()));
        foreach (['city','retailer'] as $key) if (!empty($v[$key])) $q->whereRaw('LOWER(offers.'.$key.') = LOWER(?)',[$v[$key]]);
        foreach (['brand','category'] as $key) if (!empty($v[$key])) $q->whereHas('product',fn ($query) => $query->whereRaw('LOWER('.$key.') = LOWER(?)',[$v[$key]]));
        if (!empty($v['channel'])) $q->whereIn('channel',$v['channel']==='both'?['both']:[$v['channel'],'both']);
        if (isset($v['min_price'])) $q->where('price_minor','>=',(int) round($v['min_price']*100));
        if (isset($v['max_price'])) $q->where('price_minor','<=',(int) round($v['max_price']*100));
        if (isset($v['min_discount'])) $q->whereRaw('CASE WHEN original_price_minor > price_minor THEN 100.0*(original_price_minor-price_minor)/original_price_minor ELSE 0 END >= ?',[$v['min_discount']]);
        if (isset($v['fresh_hours'])) $q->where('seen_at','>=',now()->subHours($v['fresh_hours']));
        if (!empty($v['search'])) foreach (preg_split('/\s+/u',trim($v['search'])) as $term) {
            $escaped=addcslashes($term,'%_\\');
            $q->where(function ($query) use ($escaped) {
                $query->where('title','ILIKE','%'.$escaped.'%')
                    ->orWhereHas('product',fn ($p) => $p->where('search_text','ILIKE','%'.$escaped.'%'));
            });
        }
        $sort=$v['sort']??'newest';
        if ($sort==='price_asc') $q->orderBy('price_minor');
        elseif ($sort==='price_desc') $q->orderByDesc('price_minor');
        elseif ($sort==='discount') $q->orderByRaw('CASE WHEN original_price_minor > price_minor THEN 100.0*(original_price_minor-price_minor)/original_price_minor ELSE 0 END DESC');
        else $q->orderByDesc('seen_at');
        return $q->orderByDesc('offers.id')->paginate($v['per_page']??24);
    }
    public function show(Offer $offer) {
        abort_unless($offer->active,404);
        return $offer->load('product','source:id,name,retailer');
    }
    public function filters() {
        return [
            'retailers'=>Offer::where('active',true)->distinct()->orderBy('retailer')->pluck('retailer'),
            'cities'=>Offer::where('active',true)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'brands'=>DB::table('products')->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand'),
            'categories'=>DB::table('products')->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
        ];
    }
}
