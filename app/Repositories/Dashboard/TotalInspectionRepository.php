<?php

namespace App\Repositories\Dashboard;

use App\Models\Forms\MasterDataForms;

class TotalInspectionRepository
{
    public function totalInspection(int $ppf)
    {
        return MasterDataForms::with('worker')
            ->select('hfid', 'updated_at')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->where('ppfno', $ppf)
            ->groupBy('hfid', 'updated_at')
            ->get();
    }
}
