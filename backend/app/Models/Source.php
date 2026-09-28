<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Source extends Model {
    protected $guarded=[];
    protected function casts(): array { return ['mapping'=>'array','enabled'=>'boolean','next_run_at'=>'datetime','last_run_at'=>'datetime']; }
}
