<?php

namespace App\Models\HFRW;

use Illuminate\Database\Eloquent\Model;

class MasterRecordHFRW extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hfrw_forms';
}
