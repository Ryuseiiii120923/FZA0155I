<?php

namespace App\Models\Inspector;

use Illuminate\Database\Eloquent\Model;

class Rework extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'inspector_rework';
}
