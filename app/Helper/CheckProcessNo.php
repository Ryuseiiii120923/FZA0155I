<?php

namespace App\Helper;

use App\Models\PPFHeader\ProcessM;

class CheckProcessNo
{
    public function CheckNextProcess(string $partNo, string $moldDie)
    {
        $processM = ProcessM::select(' 工程NO', '枝番')->where('品番', $partNo)->where('金型NO', $moldDie)
            ->orderBy('工程NO')->orderByDesc('枝番')->get();

        $strPCNo = '0';
        $strHFNo = '0';
        $strVINo = '0';
        $strADMNo = '0';

        foreach ($processM as $row) {
            $processNo = (string) $row->{'工程NO'};
            $branchNo  = (string) $row->{'枝番'};

            // PC
            if (in_array($processNo, ['4', '49', '50', '51']) && $strPCNo === '0') {
                $strPCNo = $branchNo;
            }

            // HF
            if (in_array($processNo, [
                '3',
                '39',
                '40',
                '41',
                '42',
                '43',
                '44',
                '45',
                '46',
                '47',
                '48'
            ]) && $strHFNo === '0') {
                $strHFNo = $branchNo;
            }

            // VI
            if (in_array($processNo, ['7', '55', '56', '57']) && $strVINo === '0') {
                $strVINo = $branchNo;
            }

            // ADM
            if ($processNo === '75' && $strADMNo === '0') {
                $strADMNo = $branchNo;
            }
        }
        return [
            'pc'  => (int) $strPCNo,
            'hf'  => (int) $strHFNo,
            'vi'  => (int) $strVINo,
            'adm' => (int) $strADMNo,
        ];
    }
}
