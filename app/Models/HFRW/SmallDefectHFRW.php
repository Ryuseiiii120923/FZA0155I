<?php

namespace App\Models\HFRW;

use Illuminate\Database\Eloquent\Model;

class SmallDefectHFRW extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hfrw_small';
}
