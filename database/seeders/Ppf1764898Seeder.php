<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Ppf1764898Seeder extends Seeder
{
    private int $ppfno = 1764898;

    private array $defects = [
        'Open Seam', 'Skip Stitch', 'Loose Thread', 'Stain Mark',
        'Uneven Hem', 'Broken Stitch', 'Wrong Measurement', 'Color Shading',
    ];

    private array $largeDefects = ['Sewing', 'Fabric', 'Trimming', 'Finishing'];

    private array $smallDefects = [
        'Loose Button', 'Crooked Label', 'Thread Tail', 'Light Stain', 'Wrinkle',
    ];

    private array $reworkTypes = [
        'Restitch', 'Re-press', 'Re-trim', 'Replace Part', 'Re-wash',
    ];

    // Number of rows to generate for each repeating detail table (>15)
    private int $detailRows = 20;

    public function run(): void
    {
        $ppfno = $this->ppfno;
        $hfid = 'HF0001';
        $formId = (string) Str::uuid();
        $inspectorId = 'INS001';
        $inspName = 'Maria Santos';
        $reworkNo = 1;
        $operation = 'Sewing';
        $process = 'Line 1';
        $updatedBy = 'user1';
        $now = Carbon::now();

        DB::connection('PRecord')->transaction(function () use (
            $ppfno, $hfid, $formId, $inspectorId, $inspName, $reworkNo,
            $operation, $process, $updatedBy, $now
        ) {
            // ---- HF (in-line finishing inspection) ----
            $hfTotalInspect = 120;
            $hfTotalNG = 10;
            $hfForRework = 3;
            $hfGoodQty = $hfTotalInspect - $hfTotalNG - $hfForRework;

            DB::connection('PRecord')->table('hf_forms')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'total_inspect' => $hfTotalInspect,
                'updated_by' => $updatedBy,
                'formId' => $formId,
                'goodQty' => $hfGoodQty,
                'totalNG' => $hfTotalNG,
                'forRework' => $hfForRework,
                'finishingProcedure' => 'Steam Press',
                'operation' => $operation,
                'process' => $process,
                'remarks' => 'Seeded test record',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('hf_defect')->insert([
                    'ppfno' => $ppfno,
                    'defect' => $this->pick($this->defects),
                    'qty' => random_int(1, 8),
                    'updated_by' => $updatedBy,
                    'formId' => $formId,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('hf_small')->insert([
                    'ppfno' => $ppfno,
                    'hfid' => $hfid,
                    'largeDefect' => $this->pick($this->largeDefects),
                    'smallDefect' => $this->pick($this->smallDefects),
                    'qty' => random_int(1, 5),
                    'updated_by' => $updatedBy,
                    'formId' => $formId,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('hf_rework')->insert([
                    'ppfno' => $ppfno,
                    'hfid' => $hfid,
                    'rework_type' => $this->pick($this->reworkTypes),
                    'qty' => random_int(1, 5),
                    'updated_by' => $updatedBy,
                    'total_inspect' => $hfTotalInspect,
                    'proceedToWork' => 'Yes',
                    'flgDone' => 'N',
                    'reworkNo' => $reworkNo,
                    'formId' => $formId,
                ]);
            }

            // ---- HFRW (rework re-inspection) ----
            $hfrwTotalInspect = $hfForRework + 5;
            $hfrwTotalNG = 1;
            $hfrwGoodQty = $hfrwTotalInspect - $hfrwTotalNG;

            DB::connection('PRecord')->table('hfrw_forms')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'total_inspect' => $hfrwTotalInspect,
                'goodQty' => $hfrwGoodQty,
                'totaNG' => $hfrwTotalNG,
                'operation' => $operation,
                'process' => $process,
                'updated_by' => $updatedBy,
                'formId' => $formId,
                'reworkNo' => $reworkNo,
            ]);

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('hfrw_defects')->insert([
                    'ppfno' => $ppfno,
                    'hfid' => $hfid,
                    'defect' => $this->pick($this->defects),
                    'qty' => random_int(1, 4),
                    'updated_by' => $updatedBy,
                    'formId' => $formId,
                    'reworkNo' => $reworkNo,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('hfrw_small')->insert([
                    'ppfno' => $ppfno,
                    'hfid' => $hfid,
                    'large_defect' => $this->pick($this->largeDefects),
                    'small_defect' => $this->pick($this->smallDefects),
                    'qty' => random_int(1, 3),
                    'updated_by' => $updatedBy,
                    'formId' => $formId,
                    'reworkNo' => $reworkNo,
                ]);
            }

            // ---- Inspector ----
            $inspTotalInspect = 120;
            $inspTotalNg = 10;
            $inspTotalRework = 3;
            $inspTotalGood = $inspTotalInspect - $inspTotalNg - $inspTotalRework;

            DB::connection('PRecord')->table('inspector_pr')->insert([
                'ppfno' => $ppfno,
                'inspectorId' => $inspectorId,
                'total_inspect' => $inspTotalInspect,
                'totalNg' => $inspTotalNg,
                'totalRework' => $inspTotalRework,
                'totalGood' => $inspTotalGood,
                'process' => $process,
                'operation' => $operation,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('inspector_defect')->insert([
                    'ppfno' => $ppfno,
                    'inspectorId' => $inspectorId,
                    'inspName' => $inspName,
                    'defect' => $this->pick($this->defects),
                    'qty' => random_int(1, 6),
                    'process' => $process,
                    'operation' => $operation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('inspector_small')->insert([
                    'ppfno' => $ppfno,
                    'inspectorId' => $inspectorId,
                    'small_defect' => $this->pick($this->smallDefects),
                    'large_defect' => $this->pick($this->largeDefects),
                    'qty' => random_int(1, 4),
                    'process' => $process,
                    'operation' => $operation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('inspector_rework')->insert([
                    'ppfno' => $ppfno,
                    'inspectorId' => $inspectorId,
                    'inspName' => $inspName,
                    'rework' => $this->pick($this->reworkTypes),
                    'qty' => random_int(1, 4),
                    'total_inspect' => $inspTotalInspect,
                    'process' => $process,
                    'operation' => $operation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // ---- VI (final visual inspection) ----
            $viFormId = (string) Str::uuid();
            $viTotalInspect = 120;
            $viTotalNG = 8;
            $viForRework = 2;
            $viGoodQty = $viTotalInspect - $viTotalNG - $viForRework;

            DB::connection('PRecord')->table('vi_forms')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'total_inspect' => $viTotalInspect,
                'updated_by' => $updatedBy,
                'formId' => $viFormId,
                'goodQty' => $viGoodQty,
                'totalNG' => $viTotalNG,
                'forRework' => $viForRework,
                'finishingProcedure' => 'Fold & Pack',
                'operation' => $operation,
                'process' => $process,
                'remarks' => 'Seeded test record',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('vi_defect')->insert([
                    'ppfno' => $ppfno,
                    'defect' => $this->pick($this->defects),
                    'qty' => random_int(1, 8),
                    'updated_by' => $updatedBy,
                    'formId' => $viFormId,
                ]);
            }

            foreach (range(1, $this->detailRows) as $n) {
                DB::connection('PRecord')->table('vi_small')->insert([
                    'ppfno' => $ppfno,
                    'hfid' => $hfid,
                    'largeDefect' => $this->pick($this->largeDefects),
                    'smallDefect' => $this->pick($this->smallDefects),
                    'qty' => random_int(1, 5),
                    'updated_by' => $updatedBy,
                    'formId' => $viFormId,
                ]);
            }

            DB::connection('PRecord')->table('vi_rework')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'rework_type' => $this->pick($this->reworkTypes),
                'qty' => $viForRework,
                'updated_by' => $updatedBy,
                'total_inspect' => $viTotalInspect,
                'proceedToWork' => 'Yes',
                'flgDone' => 'N',
                'reworkNo' => $reworkNo,
                'formId' => $viFormId,
            ]);
        });
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}