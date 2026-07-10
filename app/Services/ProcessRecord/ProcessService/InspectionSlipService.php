<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Models\mainDb\InspectionSlip;

class InspectionSlipService{
    public function handle(
        int $ppfNo,
        string $partNo,
        string $internalPartNo,
        string $inspectionType,
        string $lotNo,
        int $goodQty,
        string $action
    ): void {

        $inspectionType = $inspectionType ?: '1';

        switch ($action) {

            case 'add':

                InspectionSlip::updateOrCreate(
                    [
                        '流動NO' => $ppfNo,
                    ],
                    [
                        '品番'     => $partNo,
                        '社番'     => $internalPartNo,
                        '検査区分' => $inspectionType,
                        'LOTNO'    => $lotNo,
                        '数量'     => $goodQty,
                        '登録日'   => now(),
                        '計量日１' => now(),
                    ]
                );

                break;

            case 'edit':

                InspectionSlip::where('流動NO', $ppfNo)
                    ->update([
                        '検査区分' => $inspectionType,
                        '数量'     => $goodQty,
                        '登録日'   => now(),
                        '計量日１' => now(),
                    ]);

                break;

            case 'delete':

                InspectionSlip::where('流動NO', $ppfNo)
                    ->delete();

                break;
        }
    }

}