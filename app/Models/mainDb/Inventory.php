<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = '在庫';
    protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
