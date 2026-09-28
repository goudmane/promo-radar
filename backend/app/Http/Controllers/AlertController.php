<?php
namespace App\Http\Controllers;
use App\Models\OfferAlert;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class AlertController extends Controller {
    public function index(Request $r) {
        return OfferAlert::with(['offer.product','watch'])->where('user_id',$r->user()->id)->latest()->paginate(30);
    }
    public function read(Request $r,int $id) {
        $alert=OfferAlert::where('user_id',$r->user()->id)->findOrFail($id);
        $alert->update(['read_at'=>now()]); return $alert;
    }
}
