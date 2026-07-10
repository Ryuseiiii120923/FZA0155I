<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class Control extends Model
{
    protected $table = 'Control';
    protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
