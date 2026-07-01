<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use App\Models\Forms\MasterDataForms;

class GoodNgRepository
{
    public function getBaseQty(int $ppf){
        $goodQty = MasterDataForms::where('ppfno', $ppf)->sum('goodQty');
        $totalNg = MasterDataForms::where('ppfno', $ppf)->sum('totalNG');
        $excss = AddDefect::where('PPFNo', $ppf)->value('ExcessQty');
        $lack = AddDefect::where('PPFNo', $ppf)->value('LackingQty');
        $sample = AddDefect::where('PPFNo', $ppf)->value('SampleQty');
        $rework = AddDefect::where('PPFNo', $ppf)->value('ReworkQty');

        return([
            'goodQty' => $goodQty,
            'totalNG' => $totalNg,
            'excssQty' => $excss,
            'lackQty' => $lack,
            'sampleQty' => $sample,
            'reworkQty' => $rework
        ]);
    }
}
