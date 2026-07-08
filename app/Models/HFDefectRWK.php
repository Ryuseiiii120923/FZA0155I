<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HFDefectRWK extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'HFDefectRWK';

    public $timestamps = false;
}
