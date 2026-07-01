<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalInspection extends Model
{
    protected $table = 'FinalInspection';
    public $incrementing = false;

    public function masterData()
    {
        return $this->belongsTo(AddDefect::class, 'PPFNo', 'PPFNo');
    }
}
