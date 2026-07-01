<?php

namespace App\Models\Inspector;

use Illuminate\Database\Eloquent\Model;

class LargeDefect extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'inspector_defect';
}
