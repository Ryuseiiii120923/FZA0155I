<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use App\Models\Inspector\MasterData;
use App\Models\PPFHeader\CheckHF;
use App\Models\PPFHeader\CheckPPF;
use App\Models\PPFHeader\PostCure;
use App\Models\PPFHeader\ProcessM;
use App\Models\PPFHeader\Seihin;

class PPFRepository
{
    public function isExistinMain(int $ppf)
    {
        return AddDefect::where('PPFNo', $ppf)->exists();
    }

    public function fetchHeadData(int $ppf)
    {
        return CheckPPF::select('成形ﾛｯﾄ', '品番', '材料名', '金型NO', 'PRESSNO', '班', '作業員CD','未仕上')->where('流動NO', $ppf)->first();
    }

    public function fetchExpected(int $ppf)
    {
        return CheckHF::where('流動NO', $ppf)->pluck('合格数');
    }

    public function fetchTotalInspected(int $ppf)
    {
        return MasterData::where('ppfno', $ppf)
            ->where('operation', 'HF')
            ->sum('total_inspect');
    }


    public function isExistinPostCure(int $ppf)
    {
        return PostCure::where('PPFNo', $ppf)->exists();
    }

   public function getProcessNumber(string $partNo = "", string $moldNo = ""): array
{
    if (empty($partNo) || empty($moldNo)) {
        return [
            'pcNo'  => 0,
            'hfNo'  => 0,
            'viNo'  => 0,
            'admNo' => 0,
        ];
    }

    $result = ProcessM::select('工程NO', '枝番')
        ->where('品番', $partNo)
        ->where('金型NO', $moldNo)
        ->orderBy('工程NO')
        ->orderByDesc('枝番')
        ->get();

    $pcNo  = 0;
    $hfNo  = 0;
    $viNo  = 0;
    $admNo = 0;

    $pcProcessNos  = ['4', '49', '50', '51'];
    $hfProcessNos  = ['3', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48'];
    $viProcessNos  = ['7', '55', '56', '57'];
    $admProcessNos = ['75'];

    foreach ($result as $row) {
        $processNo = (string) $row->{'工程NO'};
        $edaban    = (string) $row->{'枝番'};

        if ($pcNo === 0  && in_array($processNo, $pcProcessNos))  $pcNo  = $edaban;
        if ($hfNo === 0  && in_array($processNo, $hfProcessNos))  $hfNo  = $edaban;
        if ($viNo === 0  && in_array($processNo, $viProcessNos))  $viNo  = $edaban;
        if ($admNo === 0 && in_array($processNo, $admProcessNos)) $admNo = $edaban;
    }

    return [
        'pcNo'  => $pcNo,
        'hfNo'  => $hfNo,
        'viNo'  => $viNo,
        'admNo' => $admNo,
    ];
}
}
