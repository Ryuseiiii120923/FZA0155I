<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HFGroup extends Model
{
    protected $connection = "PRecord";
    protected $table = 'groups';

    protected $fillable = [
        'ppfno',
        'hf_group',
        'operation'
    ];
    
   public $timestamps = false;
}
