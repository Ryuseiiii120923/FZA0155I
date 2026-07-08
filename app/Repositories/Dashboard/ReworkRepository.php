<?php

namespace App\Repositories\Dashboard;

use App\Models\Inspector\Rework;

class ReworkRepository{
    public function fetchRework(int $ppf){
        return Rework::where('ppfno', $ppf)->where('operation', 'HF')->get();
    }
}