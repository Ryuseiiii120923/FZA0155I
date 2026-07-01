<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticable;


class Worker extends Authenticable
{
    protected $table = "作業員";
    protected $primaryKey = '社員CD';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        '社員CD',
        '作業員CD'
    ];

    public function getAuthIdentifier()
    {
        return (int) $this->getKey();
    }

    public function employeeName()
    {
        return $this->hasOne(WorkerName::class, '社員CD', '社員CD');
    }
}
