<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;

class Defect extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hf_defect';
    public $timestamps = false;
}
