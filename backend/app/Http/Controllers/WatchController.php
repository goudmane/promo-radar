<?php
namespace App\Http\Controllers;
use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class WatchController extends Controller {
    public function index(Request $r) { return $r->user()->watches()->latest()->paginate(50); }
    private function rules(): array { return [
        'name'=>'required|string|max:100','include_terms'=>'required|array|min:1|max:15',
        'include_terms.*'=>'required|string|max:80','exclude_terms'=>'sometimes|array|max:15',
        'exclude_terms.*'=>'string|max:80','retailers'=>'sometimes|array|max:30','retailers.*'=>'string|max:100',
        'cities'=>'sometimes|array|max:30','cities.*'=>'string|max:100',
        'channels'=>'sometimes|array|max:3','channels.*'=>'in:online,store,both',
        'brand'=>'nullable|string|max:100','category'=>'nullable|string|max:100',
        'max_price'=>'nullable|numeric|min:0','min_discount'=>'nullable|integer|between:0,100',
        'muted'=>'boolean',
    ]; }
    private function values(array $v): array {
        $v['max_price_minor']=isset($v['max_price'])?(int)round($v['max_price']*100):null;
        unset($v['max_price']);
        foreach (['include_terms','exclude_terms'] as $key) $v[$key]=array_values(array_filter(array_map('trim',$v[$key]??[]),fn ($x)=>$x!==''));
        return $v;
    }
    public function store(Request $r) {
        $watch=$r->user()->watches()->create($this->values($r->validate($this->rules())));
        \App\Jobs\BackfillWatch::dispatch($watch->id);
        return response()->json($watch,201);
    }
    public function update(Request $r,Watch $watch) {
        abort_unless($watch->user_id===$r->user()->id,404);
        $watch->update($this->values($r->validate($this->rules())));
        if (!$watch->muted) \App\Jobs\BackfillWatch::dispatch($watch->id);
        return $watch;
    }
    public function destroy(Request $r,Watch $watch) {
        abort_unless($watch->user_id===$r->user()->id,404);
        $watch->delete(); return response()->noContent();
    }
}
