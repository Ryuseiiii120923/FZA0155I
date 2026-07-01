<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PrecordDummyDataSeeder extends Seeder
{
    /**
     * Number of PPF "production lots" to generate. Each one fans out
     * into related rows across hf_, hfrw_, inspector_, and vi_ tables.
     */
    private int $ppfCount = 10;

    private array $defects = [
        'Open Seam', 'Skip Stitch', 'Loose Thread', 'Stain Mark',
        'Uneven Hem', 'Broken Stitch', 'Wrong Measurement', 'Color Shading',
        'Misaligned Label', 'Puckering',
    ];

    private array $largeDefects = ['Sewing', 'Fabric', 'Trimming', 'Finishing'];

    private array $smallDefects = [
        'Loose Button', 'Crooked Label', 'Thread Tail', 'Light Stain', 'Wrinkle',
    ];

    private array $reworkTypes = [
        'Restitch', 'Re-press', 'Re-trim', 'Replace Part', 'Re-wash',
    ];

    private array $operations = ['Sewing', 'Finishing', 'Packing', 'Cutting', 'QC Inspection'];

    private array $processes = ['Line 1', 'Line 2', 'Line 3', 'Finishing Line'];

    private array $finishingProcedures = ['Steam Press', 'Fold & Pack', 'Tag Attach', 'Final Trim'];

    public function run(): void
    {
        DB::connection('PRecord')->transaction(function () {
            for ($i = 1; $i <= $this->ppfCount; $i++) {
                $this->seedOnePpf($i);
            }
        });
    }

    private function seedOnePpf(int $i): void
    {
        $ppfno = 100000 + $i;
        $hfid = 'HF' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
        $formId = (string) Str::uuid();
        $inspectorId = 'INS' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
        $inspName = fake()->name();
        $reworkNo = $i;
        $operation = $this->pick($this->operations);
        $process = $this->pick($this->processes);
        $updatedBy = 'user' . random_int(1, 9);
        $now = Carbon::now();

        // ---- HF (in-line finishing inspection) ----
        $hfTotalInspect = random_int(50, 200);
        $hfTotalNG = random_int(2, 20);
        $hfForRework = random_int(0, 5);
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
            'finishingProcedure' => $this->pick($this->finishingProcedures),
            'operation' => $operation,
            'process' => $process,
            'remarks' => $i % 2 === 0 ? fake()->sentence() : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (range(1, random_int(2, 4)) as $n) {
            DB::connection('PRecord')->table('hf_defect')->insert([
                'ppfno' => $ppfno,
                'defect' => $this->pick($this->defects),
                'qty' => random_int(1, 8),
                'updated_by' => $updatedBy,
                'formId' => $formId,
            ]);
        }

        foreach (range(1, random_int(2, 3)) as $n) {
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

        foreach (range(1, random_int(1, 2)) as $n) {
            DB::connection('PRecord')->table('hf_rework')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'rework_type' => $this->pick($this->reworkTypes),
                'qty' => random_int(1, 5),
                'updated_by' => $updatedBy,
                'total_inspect' => $hfTotalInspect,
                'proceedToWork' => $this->pick(['Yes', 'No']),
                'flgDone' => $this->pick(['Y', 'N']),
                'reworkNo' => $reworkNo,
                'formId' => $formId,
            ]);
        }

        // ---- HFRW (rework re-inspection) ----
        $hfrwTotalInspect = random_int(10, $hfForRework + 10);
        $hfrwTotalNG = random_int(0, 5);
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

        foreach (range(1, random_int(1, 3)) as $n) {
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

        foreach (range(1, random_int(1, 2)) as $n) {
            DB::connection('PRecord')->table('hfrw_small')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'large_defect' => $this->pick($this->largeDefects),
                'small_defect' => $this->pick($this->smallDefects),
                'qty' => random_int(1, 4),
                'updated_by' => $updatedBy,
                'formId' => $formId,
                'reworkNo' => $reworkNo,
            ]);
        }

        // ---- Inspector ----
        $inspTotalInspect = random_int(50, 200);
        $inspTotalNg = random_int(2, 20);
        $inspTotalRework = random_int(0, 5);
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

        foreach (range(1, random_int(2, 4)) as $n) {
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

        foreach (range(1, random_int(1, 3)) as $n) {
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

        foreach (range(1, random_int(1, 2)) as $n) {
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
        $viTotalInspect = random_int(50, 200);
        $viTotalNG = random_int(2, 20);
        $viForRework = random_int(0, 5);
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
            'finishingProcedure' => $this->pick($this->finishingProcedures),
            'operation' => $operation,
            'process' => $process,
            'remarks' => $i % 3 === 0 ? fake()->sentence() : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (range(1, random_int(2, 4)) as $n) {
            DB::connection('PRecord')->table('vi_defect')->insert([
                'ppfno' => $ppfno,
                'defect' => $this->pick($this->defects),
                'qty' => random_int(1, 8),
                'updated_by' => $updatedBy,
                'formId' => $viFormId,
            ]);
        }

        foreach (range(1, random_int(2, 3)) as $n) {
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

        foreach (range(1, random_int(1, 2)) as $n) {
            DB::connection('PRecord')->table('vi_rework')->insert([
                'ppfno' => $ppfno,
                'hfid' => $hfid,
                'rework_type' => $this->pick($this->reworkTypes),
                'qty' => random_int(1, 5),
                'updated_by' => $updatedBy,
                'total_inspect' => $viTotalInspect,
                'proceedToWork' => $this->pick(['Yes', 'No']),
                'flgDone' => $this->pick(['Y', 'N']),
                'reworkNo' => $reworkNo,
                'formId' => $viFormId,
            ]);
        }
    }

    private function pick(array $items)
    {
        return $items[array_rand($items)];
    }
}