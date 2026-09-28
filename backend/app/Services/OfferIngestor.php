<?php
namespace App\Services;
use App\Jobs\MatchOffer;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Source;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfferIngestor {
    private function field(array $row,array $mapping,string $key): mixed {
        $column=$mapping['fields'][$key]??$key;
        return data_get($row,$column);
    }
    private function money(mixed $value): ?int {
        if ($value===null || $value==='') return null;
        if (is_string($value)) $value=preg_replace('/[^\d,.-]/u','',$value);
        if (is_string($value)) {
            if (str_contains($value,',') && str_contains($value,'.')) {
                $value=strrpos($value,',')>strrpos($value,'.') ? str_replace(',','.',str_replace('.','',$value)) : str_replace(',','',$value);
            }
            elseif (str_contains($value,',')) $value=str_replace(',','.',$value);
        }
        if (!is_numeric($value) || (float)$value<0 || (float)$value>1000000000) return null;
        return (int)round((float)$value*100);
    }
    private function normalize(Source $source,array $row): ?array {
        $map=$source->mapping??[];
        $title=trim((string)$this->field($row,$map,'title'));
        $url=trim((string)$this->field($row,$map,'url'));
        $sku=trim((string)$this->field($row,$map,'sku'));
        $price=$this->money($this->field($row,$map,'price'));
        if (!$title || $price===null || (!$url && !$sku)) return null;
        if ($url) {
            $parts=parse_url($url);
            if (($parts['scheme']??'')!=='https' || strtolower($parts['host']??'')!==strtolower($source->allowed_host)) return null;
            $url=Str::before($url,'#');
        }
        $channel=(string)($this->field($row,$map,'channel')??'online');
        if (!in_array($channel,['online','store','both'],true)) $channel='online';
        $brand=trim((string)$this->field($row,$map,'brand'));
        $category=trim((string)$this->field($row,$map,'category'));
        $model=trim((string)$this->field($row,$map,'model'));
        $image=(string)$this->field($row,$map,'image_url');
        if ($image && !filter_var($image,FILTER_VALIDATE_URL)) $image='';
        $old=$this->money($this->field($row,$map,'original_price'));
        $attributes=[
            'title'=>Str::limit($title,255,''),'description'=>Str::limit((string)$this->field($row,$map,'description'),4000,''),
            'price_minor'=>$price,'original_price_minor'=>$old,'currency'=>Str::upper((string)($this->field($row,$map,'currency')?:'MAD')),
            'channel'=>$channel,'city'=>Str::limit((string)$this->field($row,$map,'city'),100,'')?:null,
            'url'=>$url,'image_url'=>$image?:null,
            'active'=>filter_var($this->field($row,$map,'active')??true,FILTER_VALIDATE_BOOLEAN),
        ];
        $material=$attributes;
        unset($material['image_url']);
        return [
            'key'=>hash('sha256',$sku?:$url),'name'=>$title,'brand'=>$brand?:null,'model'=>$model?:null,'category'=>$category?:null,
            'attributes'=>$attributes,'revision'=>hash('sha256',json_encode($material,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)),
        ];
    }
    public function ingest(Source $source,array $rows): array {
        $changed=0; $skipped=0;
        foreach ($rows as $row) {
            $data=$this->normalize($source,$row);
            if (!$data) { $skipped++; continue; }
            $didChange=DB::transaction(function () use ($source,$data) {
                $existing=Offer::where('source_id',$source->id)->where('external_key',$data['key'])->lockForUpdate()->first();
                if ($existing && $existing->revision===$data['revision']) {
                    $existing->update(['seen_at'=>now()]);
                    return false;
                }
                $product=$existing?->product ?: Product::create([
                    'name'=>$data['name'],'brand'=>$data['brand'],'model'=>$data['model'],
                    'category'=>$data['category'],'search_text'=>Str::lower(implode(' ',array_filter([$data['name'],$data['brand'],$data['model'],$data['category']]))),
                ]);
                if ($existing) {
                    $product->update(['name'=>$data['name'],'brand'=>$data['brand'],'model'=>$data['model'],
                        'category'=>$data['category'],'search_text'=>Str::lower(implode(' ',array_filter([$data['name'],$data['brand'],$data['model'],$data['category']])))]);
                    $existing->update([...$data['attributes'],'revision'=>$data['revision'],'product_id'=>$product->id,'seen_at'=>now()]);
                    $offer=$existing;
                } else {
                    $offer=Offer::create([...$data['attributes'],'revision'=>$data['revision'],'product_id'=>$product->id,
                        'source_id'=>$source->id,'external_key'=>$data['key'],'retailer'=>$source->retailer,'seen_at'=>now()]);
                }
                DB::table('price_snapshots')->insert(['offer_id'=>$offer->id,'price_minor'=>$offer->price_minor,
                    'original_price_minor'=>$offer->original_price_minor,'observed_at'=>now()]);
                MatchOffer::dispatch($offer->id,$offer->revision)->afterCommit();
                return true;
            });
            if ($didChange) $changed++;
        }
        return ['received'=>count($rows),'changed'=>$changed,'skipped'=>$skipped];
    }
}
