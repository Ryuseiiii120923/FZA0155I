<?php

namespace App\Repositories;

use App\Models\AddDefect;
use App\Models\HFGroup;
use App\Repositories\Defect\DefectRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SaveRepository
{
    public function saveMain(int $ppf, array $header, array $defects, array $smallDefects, array $goodNg, array $otherDetails, array $inspection): bool
    {

        try {
            $inspectorCols = [
                'HFNo1' => $inspection['insp1'] ?? '',
                'HFNo2' => $inspection['insp2'] ?? '',
                'HFNo3' => $inspection['insp3'] ?? '',
                'HFNo4' => $inspection['insp4'] ?? '',
                'HFNo5' => $inspection['insp5'] ?? '',
            ];

            $hfGroup = [
                'ppfno' => $ppf,
                'hf_group' => $otherDetails['hfGroup'],
                'operation' => 'HF'
            ];

            HFGroup::upsert(
                $hfGroup,
                ['ppfno', 'operation'],
                ['hf_group']
            );

            $rows = [];
            $hfDefectRWKRows = [];
            $hfDefectSmall = [];
            if (empty($defects)) {
                // No defects submitted — still save one row so the inspection is recorded.
                $rows[] = array_merge($inspectorCols, [
                    'PPFNo'      => (float) $ppf,
                    'PartNo'     => $header['partno'],
                    'LotNo'      => $header['lotno'],
                    'MatNo'      => $header['matno'],
                    'MDNo'       => $header['moldno'],
                    'Shift'      => $header['shift'],
                    'Operator'   => substr(trim($header['opt']), 0, 2),
                    'Total'      => $header['totalInspected'],
                    'Good'       => $goodNg['goodqty'],
                    'Defect'     => '',
                    'Quantity'   => 0,
                    'Details'    => $otherDetails['details'] ?? '',
                    'HFDate'     => $otherDetails['hfDate'] ?? null,
                    'Encoder'    => (int) ($otherDetails['encoder'] ?? 0),
                    'DateEncode' => Carbon::now()->format('Y-m-d h:i:s'),
                    'ExcessQty'  => $goodNg['excssqty'],
                    'LackingQty' => $goodNg['lackqty'],
                    'ReworkQty'  => $goodNg['reworkqty'],
                    'SampleQty'  => $goodNg['sampleqty'],
                    'FinMachine' => isset($otherDetails['finishingMachine'])
                        ? substr(implode(',', (array) $otherDetails['finishingMachine']), 0, 50)
                        : '',
                ]);
            } else {
                foreach ($defects as $defect) {
                    $type = $defect['type'] ?? $defect['newDefect'] ?? null;
                    $qty  = isset($defect['qty']) ? (float) $defect['qty'] : (float) ($defect['newQuan'] ?? 0);

                    $hfDefectRWKRows[] = [
                        'PPFNo' => (float) $ppf,
                        'Defect' => $type,
                        'Quantity' => $qty,
                        'TotalInspQty' => $header['expct'],
                        'HFNo' => $defect['operatorid'],
                    ];

                    $rows[] = array_merge($inspectorCols, [
                        'PPFNo'      => (float) $ppf,
                        'PartNo'     => $header['partno'],
                        'LotNo'      => $header['lotno'],
                        'MatNo'      => $header['matno'],
                        'MDNo'       => $header['moldno'],
                        'Shift'      => $header['shift'],
                        'Operator'   => substr(trim($header['opt']), 0, 2),
                        'Total'      => $header['totalInspected'],
                        'Good'       => $goodNg['goodqty'],
                        'Defect'     => $type ?? "",
                        'Quantity'   => $qty ?? 0,
                        'Details'    => $otherDetails['details'] ?? '',
                        'HFDate'     => $otherDetails['hfDate'] ?? null,
                        'Encoder'    => (int) ($otherDetails['encoder'] ?? 0),
                        'DateEncode' => Carbon::now()->format('Y-m-d h:i:s'),
                        'ExcessQty'  => $goodNg['excssqty'],
                        'LackingQty' => $goodNg['lackqty'],
                        'ReworkQty'  => $goodNg['reworkqty'],
                        'SampleQty'  => $goodNg['sampleqty'],
                        'FinMachine' => isset($otherDetails['finishingMachine'])
                            ? substr(implode(',', (array) $otherDetails['finishingMachine']), 0, 50)
                            : '',
                    ]);
                }
            }

            foreach ($smallDefects as $largeDefect => $processes) {
                foreach ($processes as $process => $inspectors) {
                    foreach ($inspectors as $inspectorId => $smallDefectsList) {
                        foreach ($smallDefectsList as $smallDefect) {
                            $hfDefectSmall[] = [
                                'PPFNo' => (float) $ppf,
                                'LargeDefect' => $largeDefect,
                                'SmallDefect' => $smallDefect['type'],
                                'Qty' => (float) $smallDefect['qty'],
                                'dFlg' => 'HF'
                            ];
                        }
                    }
                }
            }

            if (!empty($hfDefectSmall)) {
                app(DefectRepository::class)->saveHfDefectSmall($hfDefectSmall);
            }

            if (!empty($hfDefectRWKRows)) {
                app(DefectRepository::class)->saveHfDefectRWK($hfDefectRWKRows);
            }
            if (empty($rows)) {
                return false;
            }

            return AddDefect::insert($rows);
        } catch (\Throwable $e) {
            Log::error('Error in SaveRepository::saveMain', [
                'ppf'     => $ppf,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function deleteMain(int $ppf)
    {
        try {
            $deleteMain = AddDefect::where('PPFNo', $ppf)->delete();
            $deleteSmall = app(DefectRepository::class)->deleteHfDefectSmall($ppf);

            return [
                $deleteMain,
                $deleteSmall
            ];
        } catch (\Throwable $e) {
            Log::error('Error in SaveRepository::deleteMain', [
                'ppf'     => $ppf,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
