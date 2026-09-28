<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Watch extends Model {
    protected $guarded=[];
    protected function casts(): array { return [
        'include_terms'=>'array','exclude_terms'=>'array','retailers'=>'array',
        'cities'=>'array','channels'=>'array','muted'=>'boolean',
    ]; }
}
