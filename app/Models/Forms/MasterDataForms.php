<?php

namespace App\Models\Forms;

use App\Models\HFDetails\HFID;
use Illuminate\Database\Eloquent\Model;

class MasterDataForms extends Model
{
    protected $connection = 'PRecord';
    protected $table = 'hf_forms';

    public function worker()
    {
        return $this->belongsTo(HFID::class, 'hfid', '作業員CD');
    }
}
