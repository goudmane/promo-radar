<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
    use HasApiTokens, Notifiable;
    protected $fillable = ['name','email','password','is_owner','email_mode'];
    public function watches() { return $this->hasMany(Watch::class); }
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['email_verified_at'=>'datetime','password'=>'hashed','is_owner'=>'boolean']; }
}
