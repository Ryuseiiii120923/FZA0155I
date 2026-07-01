<?php

namespace App\Models\Auth;

use Illuminate\Foundation\Auth\User as Authenticable;
use Illuminate\Database\Eloquent\Model;

class Employee extends Authenticable
{
    protected $table = 'PASWORD';
    protected $primaryKey = '社員CD';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        '社員CD',
        'PASSWORD',
        '名前'
    ];
     protected $hidden = [
        'PASSWORD',
    ];
    public function getAuthIdentifierName()
    {
        return '社員CD';
    }

    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }
    public function employeeName()
    {
        return $this->hasOne(WorkerName::class, '社員CD', '社員CD');
    }
}
