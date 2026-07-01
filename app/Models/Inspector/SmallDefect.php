<?php

namespace App\Models\Inspector;

use Illuminate\Database\Eloquent\Model;

class SmallDefect extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'inspector_small';
}
