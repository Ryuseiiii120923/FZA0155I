<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class InspectionSlip extends Model
{
    protected $table = '検査伝票';

    public $timestamps = false;

    protected $fillable = [
        '流動NO',
        '品番',
        '社番',
        '検査区分',
        'LOTNO',
        '数量',
        '登録日',
        '計量日１',
    ];

     protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
}
