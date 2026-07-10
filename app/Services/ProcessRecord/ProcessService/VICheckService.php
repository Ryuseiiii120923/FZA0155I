<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Helper\CheckProcessNo;
use App\Models\mainDb\VICheck;

class VICheckService{
     public function handle(
        string $action,
        int $ppfNo,
        string $partNo,
        string $lotNo,
        int $goodQty,
        int $employeeId,
        string $computerName,
        string $moldDie
    ): void {

        $checkProcess = app(CheckProcessNo::class)->CheckNextProcess($partNo, $moldDie);
        $shouldInsert = $checkProcess['pc'] < $checkProcess['hf']
    && $checkProcess['hf'] < $checkProcess['vi']
    && $checkProcess['adm'] < $checkProcess['vi'];
        if (! $shouldInsert) {
            return;
        }

        switch ($action) {

            case 'add':

                VICheck::create([
                    'PPFNo'     => $ppfNo,
                    'PartNo'    => $partNo,
                    'LotNo'     => $lotNo,
                    'QtyIn'     => str_pad($goodQty, 7, '0', STR_PAD_LEFT),
                    'EncoderIn' => $employeeId,
                    'DateIn'    => now(),
                    'Computer'  => $computerName,
                ]);

                break;

            case 'edit':

                VICheck::where('PPFNo', $ppfNo)
                    ->update([
                        'QtyIn'     => str_pad($goodQty, 7, '0', STR_PAD_LEFT),
                        'EncoderIn' => $employeeId,
                        'DateIn'    => now(),
                        'Computer'  => $computerName,
                    ]);

                break;

            case 'delete':

                VICheck::where('PPFNo', $ppfNo)->delete();

                break;
        }
    }
}