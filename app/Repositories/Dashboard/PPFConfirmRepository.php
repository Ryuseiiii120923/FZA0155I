<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use App\Models\Inspector\MasterData;
use App\Models\PPFHeader\CheckHF;
use Illuminate\Support\Facades\DB;

class PPFConfirmRepository
{

    public function fetchData(?string $search = null, int $perPage = 20)
    {
        $paginated = MasterData::select('ppfno')
        ->Where('operation', 'HF')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('FF.dbo.HFDefect')
                    ->whereRaw("
                    CAST(CAST(FF.dbo.HFDefect.PPFNo AS BIGINT) AS NVARCHAR(50))
                    = PRecord.dbo.inspector_pr.ppfno
                ");
            })
            ->when($search, function ($query) use ($search) {
                $query->where('ppfno', 'like', "%{$search}%");
            })
            ->groupBy('ppfno')
            ->orderByRaw('MAX(updated_at) DESC')
            ->paginate($perPage);

        // Avoid N+1 queries
        $ppfNos = $paginated->getCollection()
            ->pluck('ppfno')
            ->map(fn($ppf) => (int) $ppf);

        $checkHF = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->keyBy('流動NO');

        $paginated->getCollection()->transform(function ($item) use ($checkHF) {
            $hf = CheckHF::where('流動NO', (int) $item->ppfno)->first();
            $item->expct = $hf ? round($hf->未仕上) : 0;
            return $item;
        });
        return $paginated;
    }

    public function fetchSavedData(?string $search = null, int $perPage = 20)
    {
        $paginated = AddDefect::with('finalInspection')
            ->selectRaw('PPFNo as ppfno')
            ->selectRaw('MAX(Total) as total_inspect')
            ->selectRaw('MAX(DateEncode) as updated_at')
            ->when($search, function ($q) use ($search) {
                $q->whereRaw(
                    "CAST(CAST(PPFNo AS BIGINT) AS NVARCHAR(50)) LIKE ?",
                    ["%{$search}%"]
                );
            })
            ->groupBy('PPFNo')
            ->paginate($perPage);

        $ppfNos = $paginated->getCollection()
            ->pluck('ppfno')
            ->map(fn($ppf) => (int) $ppf);

        $checkHF = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->keyBy('流動NO');
        $paginated->getCollection()->transform(function ($item) use ($checkHF) {
            $hf = $checkHF->get($item->ppfno);
            $item->expct = $hf ? round($hf->未仕上) : 0;
            return $item;
        });
        return $paginated;
    }
}
