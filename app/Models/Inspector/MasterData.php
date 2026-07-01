<?php

namespace App\Models\Inspector;

use App\Models\AddDefect;
use App\Models\FinalInspection;
use App\Models\HFDetails\HFID;
use Illuminate\Database\Eloquent\Model;

class MasterData extends Model
{
    protected $connection = 'PRecord';
     protected $table = "inspector_pr";
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'inspectorId',
        'ppfno',
        'total_inspect',
        'created_at',
        'updated_at',
        'process'
    ];


    public function worker()
    {
        return $this->belongsTo(HFID::class, 'InspectorID', '作業員CD');
    }

    public function inDefect(){
        return $this->belongsTo(AddDefect::class,'ppfno', 'PPFNo');
    }

}
