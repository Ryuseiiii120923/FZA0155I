<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use Illuminate\Support\Facades\DB;

class OtherDetailsRepository
{
    public function fetchDetails(int $ppf)
    {
        return AddDefect::select('Details', 'DateEncode', 'Encoder', 'HFDate', 'FinMachine')
            ->where('PPFNo', $ppf)
            ->first();
    }

    public function fetchHFMachine()
    {
        return DB::table('FMParameters')
            ->whereNotNull('FinishingMachines')
            ->pluck('FinishingMachines');
    }
}
