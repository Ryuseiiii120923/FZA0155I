<?php

namespace App\Models\HFRW;

use Illuminate\Database\Eloquent\Model;

class DefectHFRW extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hfrw_defects';
}
