<?php
namespace App\Http\Controllers;
use App\Jobs\CollectSource;
use App\Models\Source;
use App\Services\OfferIngestor;
use App\Services\SourceUrlPolicy;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class SourceController extends Controller {
    public function index() { return Source::query()->latest()->paginate(50); }
    private function rules(): array { return [
        'name'=>'required|string|max:100','retailer'=>'required|string|max:100',
        'driver'=>['required',Rule::in(['json_feed','browser'])],
        'url'=>'required|url|max:2000','allowed_host'=>'required|string|max:255',
        'interval_minutes'=>'required|integer|between:10,10080',
        'enabled'=>'sometimes|boolean','mapping'=>'nullable|array',
        'mapping.fields'=>'sometimes|array','mapping.selectors'=>'sometimes|array',
        'mapping.item_selector'=>'sometimes|string|max:500',
        'mapping.next_selector'=>'sometimes|string|max:500',
        'mapping.max_pages'=>'sometimes|integer|between:1,50',
    ]; }
    public function store(Request $r,SourceUrlPolicy $policy) {
        $v=$r->validate($this->rules()); $policy->assertAllowed($v['url'],$v['allowed_host']);
        return response()->json(Source::create($v),201);
    }
    public function update(Request $r,Source $source,SourceUrlPolicy $policy) {
        $v=$r->validate($this->rules()); $policy->assertAllowed($v['url'],$v['allowed_host']);
        $source->update($v); return $source;
    }
    public function destroy(Source $source) { $source->delete(); return response()->noContent(); }
    public function run(Source $source) {
        CollectSource::dispatch($source->id,true);
        return response()->json(['queued'=>true],202);
    }
    public function import(Request $r,OfferIngestor $ingestor) {
        $v=$r->validate(['source_id'=>'required|integer|exists:sources,id','rows'=>'required|array|max:1000','rows.*'=>'required|array']);
        return $ingestor->ingest(Source::findOrFail($v['source_id']),$v['rows']);
    }
}
