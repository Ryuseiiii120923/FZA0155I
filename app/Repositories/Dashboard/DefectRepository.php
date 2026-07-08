<?php

namespace App\Repositories\Dashboard;

use App\Models\Inspector\LargeDefect;
use App\Models\Inspector\SmallDefect;
use Illuminate\Support\Facades\DB;

class DefectRepository
{
    public function getSmallDefectsForPpf(int $ppf)
    {
        return SmallDefect::selectRaw('large_defect, process, inspectorId, small_defect, SUM(qty) as total_qty')
            ->where('ppfno', $ppf)
            ->where('operation', 'HF')
            ->groupBy('large_defect', 'process', 'inspectorId', 'small_defect')
            ->get()
            ->groupBy(fn($r) => $r->large_defect  . '||' . $r->process . '||' . $r->inspectorId);
    }

    public function getDefectsGrouped(int $ppf)
    {
        return LargeDefect::select(
                'inspectorId',
                'inspName',
                'defect',
                'process',
                DB::raw('SUM(qty) as total_qty'),
                DB::raw('MAX(created_at) as latest_date')
            )
            ->where('ppfno', $ppf)
            ->where('operation', 'HF')
            ->whereNotNull('inspectorId')
            ->groupBy('inspectorId', 'inspName', 'defect', 'process')
            ->orderBy('inspectorId')
            ->get();
    }
}
