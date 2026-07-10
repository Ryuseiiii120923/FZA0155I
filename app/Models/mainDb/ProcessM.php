<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class ProcessM extends Model
{
    protected $table = '工程M';
     protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
