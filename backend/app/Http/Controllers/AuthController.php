<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller {
    public function register(Request $r) {
        $v=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:12|max:255']);
        $user=User::create([...$v,'password'=>Hash::make($v['password']),'is_owner'=>false]);
        return response()->json(['user'=>$user,'token'=>$user->createToken('client')->plainTextToken],201);
    }
    public function login(Request $r) {
        $v=$r->validate(['email'=>'required|email','password'=>'required|string']);
        $user=User::where('email',$v['email'])->first();
        if (!$user || !Hash::check($v['password'],$user->password)) throw ValidationException::withMessages(['email'=>'Invalid credentials.']);
        return ['user'=>$user,'token'=>$user->createToken('client')->plainTextToken];
    }
    public function me(Request $r) { return $r->user(); }
    public function logout(Request $r) { $r->user()->currentAccessToken()->delete(); return response()->noContent(); }
    public function preferences(Request $r) {
        $v=$r->validate(['email_mode'=>'required|in:off,immediate,digest']);
        $r->user()->update($v);
        return $r->user()->fresh();
    }
}
