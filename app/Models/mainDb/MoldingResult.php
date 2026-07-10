<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class MoldingResult extends Model
{
    protected $table = '成形実績';

     protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
