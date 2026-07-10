<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class VICheck extends Model
{
    protected $table = 'VICheck';
    protected $fillable = [
        'PPFNo',
        'PartNo',
        'LotNo',
        'QtyIn',
        'EncoderIn',
        'DateIn',
        'Computer',
    ];
    protected $primaryKey = 'RecNo';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
