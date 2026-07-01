<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;

class Rework extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hf_rework';
    public $timestamps = false;
}
