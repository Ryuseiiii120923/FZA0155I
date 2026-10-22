<?php

namespace App\Repositories\ProcessRecord;

use App\Models\Forms\MasterDataForms;
use App\Models\Forms\SubMasterDataForms;
use Illuminate\Database\Eloquent\Collection;

class SubProcessRepository
{
    public function forPpf(int $ppf): Collection
    {
        return SubMasterDataForms::with('worker.employeeName')
            ->where('ppfno', $ppf)
            ->get();
    }

    /** parent formId => hfid */
    public function parentHfidMap(int $ppf): array
    {
        return MasterDataForms::where('ppfno', $ppf)->pluck('hfid', 'formId')->all();
    }

    /** sub hfid => parent hfid, for every sub process of a PPF */
    public function parentHfidBySubHfid(int $ppf): array
    {
        $subs = SubMasterDataForms::where('ppfno', $ppf)->get(['hfid', 'parentFormId']);

        if ($subs->isEmpty()) {
            return [];
        }

        $parentHf = MasterDataForms::where('ppfno', $ppf)
            ->whereIn('formId', $subs->pluck('parentFormId')->unique())
            ->pluck('hfid', 'formId');

        return $subs
            ->mapWithKeys(fn($s) => [trim((string) $s->hfid) => trim((string) ($parentHf[$s->parentFormId] ?? ''))])
            ->all();
    }
}
