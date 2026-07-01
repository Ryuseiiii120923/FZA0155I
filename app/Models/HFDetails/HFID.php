<?php

namespace App\Models\HFDetails;

use App\Models\Auth\WorkerName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class HFID extends Authenticatable
{
    protected $connection = 'sqlsrv';
    protected $table = "作業員";
    protected $primaryKey = '社員CD';
    public $incrementing = false;
    public $timestamps = false;

    public function employeeName()
    {
        return $this->hasOne(WorkerName::class, '社員CD', '社員CD');
    }
}
