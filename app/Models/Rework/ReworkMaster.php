<?php

namespace App\Models\Rework;

use Illuminate\Database\Eloquent\Model;

class ReworkMaster extends Model
{
     protected $table="RWKDefectType";
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'DefectType';
    protected $keyType = 'string';
    protected $fillable = [
        'DefectType',
    ];
}
