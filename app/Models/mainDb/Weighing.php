<?php

namespace App\Models\mainDb;

use Illuminate\Database\Eloquent\Model;

class Weighing extends Model
{
    protected $table = '計量１';

    protected $fillable = [
        '登録NO',
        '社員CD',
        '作業員CD',
        '流動NO',
        '仕上日',
        '計量日',
        '品番',
        '社内品番',
        'ﾛｯﾄNO',
        '総数',
        '合格数',
        '単価係数',
        '再検',
        '移動',
        '入力日',
        '登録者',
        'タイム',
    ];

    protected $primaryKey = 'RECNO';
    public $incrementing = 'false';
    protected $keytype = 'string';
    public $timestamps = false;
}
