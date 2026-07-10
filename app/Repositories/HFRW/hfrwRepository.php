<?php

namespace App\Repositories\HFRW;

use App\Models\Auth\WorkerName;
use App\Models\HFRW\DefectHFRW;
use App\Models\HFRW\MasterRecordHFRW;
use App\Models\HFRW\SmallDefectHFRW;
use App\Models\HFRW\ViRework;
use App\Models\Inspector\LargeDefect;
use App\Models\Inspector\SmallDefect;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestSize\Small;

class hfrwRepository
{
    public function fetchPendingRework(?string $search = null, int $perPage = 5)
    {
        return ViRework::select('ppfno', 'reworkNo', 'flgDone', 'created_at')
            ->selectRaw('SUM(qty) as qty')
            ->where('proceedToWork', 1)
            ->when($search, function ($query) use ($search) {
                $query->where('ppfno', 'like', '%' . $search . '%');
            })
            ->groupBy('ppfno', 'reworkNo', 'flgDone', 'created_at')
            ->orderByRaw('created_at DESC')
            ->paginate($perPage);
    }


    //------------Saving Section -----------------
    public function saveMainForm(array $data)
    {
        try {
            $forms = [];
            $forms[] = [
                'hfid' => $data['hfid'],
                'total_inspect' => $data['total_inspect'],
                'updated_by' => $data['encoder'],
                'ppfno' => $data['ppfno'],
                'goodQty' => $data['goodQty'],
                'inspect_REC' => $data['inspect_REC'],
                'reworkNo' => $data['reworkNo'],
                'process' => 'HFRW',
                'operation' => 'HF',
                'totalNg' => $data['totalNg']
            ];


            MasterRecordHFRW::upsert(
                $forms,
                ['inspect_REC', 'ppfno', 'reworkNo'],
                ['goodQty', 'updated_by', 'total_inspect', 'hfid', 'totalNg']
            );
        } catch (\Throwable $e) {
            
            throw new \Exception("Failed to save DR form: " . $e->getMessage());
        }
    }

    public function saveDefects(int $hfId, array $defects, int $ppfno, int $encoder, string $inspectRec, int $reworkNo)
    {
        try {

            $drRows = [];
            $inspectorRows = [];

            foreach ($defects as $defect) {
                if (empty($defect['type'])) {
                    throw new \Exception("Defect type cannot be empty.");
                }

                $drRows[] = [
                    'hfid' => $hfId,
                    'defect' => $defect['type'],
                    'qty' => $defect['qty'],
                    'updated_by' => $encoder,
                    'ppfno' => $ppfno,
                    'inspect_REC' => $inspectRec,
                    'reworkNo' => $reworkNo
                ];
            }

            $groupedDefects = collect($defects)
                ->groupBy('type')
                ->map(function ($items, $type) {
                    return [
                        'type' => $type,
                        'qty' => collect($items)->sum('qty')
                    ];
                })
                ->values();

            foreach ($groupedDefects as $defect) {

                $inspectorRows[] = [
                    'ppfno' => $ppfno,
                    'inspectorId' => $hfId,
                    'inspName' => WorkerName::where('社員CD', $hfId)->value('名前'),
                    'defect' => $defect['type'],
                    'qty' => $defect['qty'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'process' => 'HFRW',
                    'operation' => 'HF',
                ];
            }
            DefectHFRW::upsert(
                $drRows,
                ['ppfno', 'defect', 'inspect_REC'],
                ['qty', 'updated_by', 'hfid', 'ppfno', 'reworkNo']
            );

            LargeDefect::upsert(
                $inspectorRows,
                ['ppfno',  'defect', 'process', 'inspectorId'], // unique keys
                ['qty', 'updated_at']
            );
        } catch (\Throwable $e) {
            
            throw new \Exception("Failed to save defects: " . $e->getMessage());
        }
    }

    public function saveSmallDefects(int $hfId, array $smalldefects, string $ppfno, string $encoder, string $inspectRec, int $reworkNo)
    {
        try {
            $drsmallrows = [];
            $inspectorSmallRows = [];
            foreach ($smalldefects as $large => $smalls) {
                foreach ($smalls as $small) {

                    if (empty($small['type'])) {
                        throw new \Exception("Small defect type for {$large} cannot be empty.");
                    }

                    $drsmallrows[] = [
                        'hfid' => $hfId,
                        'large_defect' => $large,
                        'small_defect' => $small['type'],
                        'qty' => $small['qty'],
                        'updated_by' => $encoder,
                        'ppfno' => $ppfno,
                        'inspect_REC' => $inspectRec,
                        'reworkNo' => $reworkNo
                    ];
                }
            }

            $groupedSmall = collect($smalldefects)
                ->flatMap(function ($smalls, $large) {
                    return collect($smalls)->map(function ($small) use ($large) {
                        return [
                            'large' => $large,
                            'type' => $small['type'],
                            'qty' => $small['qty']
                        ];
                    });
                })
                ->groupBy(fn($item) => $item['large'] . '|' . $item['type'])
                ->map(function ($items) {
                    $first = $items->first();
                    return [
                        'large' => $first['large'],
                        'type' => $first['type'],
                        'qty' => $items->sum('qty')
                    ];
                })
                ->values();

            foreach ($groupedSmall as $small) {

                $inspectorSmallRows[] = [
                    'ppfno' => $ppfno,
                    'inspectorId' => $encoder,
                    'large_defect' => $small['large'],
                    'small_defect' => $small['type'],
                    'qty' => $small['qty'],
                    'process' => 'HFRW',
                    'operation' => 'HF',
                ];
            }

            // ✅ Run UPSERT once
            if (!empty($drsmallrows)) {
                SmallDefectHFRW::upsert(
                    $drsmallrows,
                    ['hfid', 'ppfno', 'large_defect', 'small_defect', 'inspect_REC'], // better unique key
                    ['qty', 'reworkNo']
                );
            }

            if (!empty($inspectorSmallRows)) {
                SmallDefect::upsert(
                    $inspectorSmallRows,
                    ['large_defect', 'small_defect', 'process', 'inspectorId'],
                    ['qty']
                );
            }
        } catch (\Throwable $e) {
            
            throw new \Exception("Failed to save small defects: " . $e->getMessage());
        }
    }

    public function updateFlag($ppf, $reworkNo)
    {
        try {
            ViRework::where('ppfno', $ppf)
                ->where('reworkNo', $reworkNo)
                ->update([
                    'FlgDone' => 1
                ]);
        } catch (\Throwable $e) {
            
            throw new \Exception("Update to update Flag: " . $e->getMessage());
        }
    }

    // -------------------End Saving Section -------------------


    // ------------------ Delete Section ------------------

    public function deleteDoneReworkByPPF($ppf, $reworkNo)
    {


        DB::transaction(function () use ($ppf, $reworkNo) {
            DefectHFRW::where('ppfno', $ppf)
                ->where('reworkNo', $reworkNo)
                ->delete();

            SmallDefectHFRW::where('ppfno', $ppf)
                ->where('reworkNo', $reworkNo)
                ->delete();

            MasterRecordHFRW::where('ppfno', $ppf)
                ->where('ReworkNo', $reworkNo)
                ->delete();

            LargeDefect::where('ppfno', $ppf)
                ->where('process', 'HFRW')
                ->delete();
            SmallDefect::where('ppfno', $ppf)
                ->where('process', 'HFRW')
                ->delete();
        });

        return true;
    }

    public function updateflagdoneforDelete($ppf, $reworkNo)
    {
        return ViRework::where('ppfno', $ppf)
            ->where('reworkNo', $reworkNo)
            ->update(['flgDone' => 0]);
    }

    // -----------------End of Delete Section -----------------

    // ----------------- Fetching Section -----------------

    public function fetchReworkDetails(int $ppf, int $reworkNo, int $inspectorId)
    {
        return MasterRecordHFRW::where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->where('ReworkNo', $reworkNo)
            ->get();
    }

    public function fetchDefects(int $ppf, string $inspectRec)
    {
        return DefectHFRW::where('ppfno', $ppf)
            ->where('inspect_REC', $inspectRec)
            ->get();
    }

    public function fetchSmallDefects(int $ppf, string $inspectRec)
    {
        return SmallDefectHFRW::where('ppfno', $ppf)
            ->where('inspect_REC', $inspectRec)
            ->get();
    }

    // ----------------- End of Fetching Section -----------------

    // ----------------- Delete Form -----------------

    public function deleteForm($formId, $ppfno)
    {
        try {
            MasterRecordHFRW::where('inspect_REC', $formId)
                ->where('ppfno', $ppfno)
                ->delete();

            DefectHFRW::where('inspect_REC', $formId)
                ->where('ppfno', $ppfno)
                ->delete();

            SmallDefectHFRW::where('inspect_REC', $formId)
                ->where('ppfno', $ppfno)
                ->delete();
        } catch (\Throwable $e) {
            
            throw new \Exception("Failed to delete form: " . $e->getMessage());
        }
    }

    public function deleteLargeDefect($ppfno, $type, $formId)
    {
        try {
            DefectHFRW::where('ppfno', $ppfno)
                ->where('defect', $type)
                ->where('inspect_REC', $formId)
                ->delete();
            SmallDefectHFRW::where('ppfno', $ppfno)
                ->where('large_defect', $type)
                ->where('inspect_REC', $formId)
                ->delete();
        } catch (\Throwable $e) {
            throw new \Exception("Failed to delete large defect: " . $e->getMessage());
        }
    }

    public function deleteSmallDefect($ppfno, $large, $type, $formId)
    {
        try {
            SmallDefectHFRW::where('ppfno', $ppfno)
                ->where('large_defect', $large)
                ->where('small_defect', $type)
                ->where('inspect_REC', $formId)
                ->delete();
        } catch (\Throwable $e) {
            
            throw new \Exception("Failed to delete small defect: " . $e->getMessage());
        }
    }

    // ----------------- End of Delete Form -----------------
}
