<?php

namespace App\Models\PPFHeader;

use Illuminate\Database\Eloquent\Model;

class CheckHF extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = "成形実績";
}
