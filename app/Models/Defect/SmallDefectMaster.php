<?php

namespace App\Models\Defect;

use Illuminate\Database\Eloquent\Model;

class SmallDefectMaster extends Model
{
    protected $table = "DefectSMALL";
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'PPFNo',
        'LargeDefect',
        'SmallDefect',
        'Qty'
    ];
}
