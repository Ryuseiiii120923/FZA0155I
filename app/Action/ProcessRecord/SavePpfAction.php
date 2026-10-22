<?php

namespace App\Action\ProcessRecord;

use App\Models\Auth\Worker;
use App\Models\Auth\WorkerName;
use App\Repositories\Defect\DefectRepository;
use App\Repositories\ProcessRecord\MasterDataPRRepository;
use App\Repositories\Rework\ReworkRepository;
use App\Support\ProcessType;
use Illuminate\Support\Facades\DB;

// app/Actions/Ppf/SavePpfAction.php

class SavePpfAction
{
    public function __construct(
        private MasterDataPRRepository $masterRepo,
        private DefectRepository $defectRepo,
        private ReworkRepository $reworkRepo,
    ) {}

    public function execute(array $forms, mixed $ppf, int $encoder, array $pendingDeletes): void
    {
        $this->assertValidHierarchy($forms, $pendingDeletes);

        DB::transaction(function () use ($encoder, $forms, $ppf, $pendingDeletes) {
            foreach ($pendingDeletes['forms'] as $form) {
                $this->masterRepo->deleteFormCascade($form['formId']);
            }
            foreach ($pendingDeletes['defects'] as $d) {
                $this->defectRepo->deleteDefect($d['formId'], $d['type']);
            }
            foreach ($pendingDeletes['smallDefects'] as $s) {
                $this->defectRepo->deleteSmallDefect($s['formId'], $s['largeDefect'], $s['type']);
            }
            foreach ($pendingDeletes['reworks'] as $r) {
                $this->reworkRepo->deleteRework($r['formId'], $r['hfno'], $r['type']);
            }

            $this->saveSubData($forms, $ppf, $encoder);
            $this->saveGeneralData($forms, $ppf, $encoder);
        });
    }

    private function assertValidHierarchy(array $forms, array $pendingDeletes): void
    {
        $deletedIds = array_column($pendingDeletes['forms'] ?? [], 'formId');

        foreach ($forms as $form) {
            $parentId = $form['parent_form_id'] ?? null;

            if (! $parentId) {
                continue;
            }

            if ($parentId === ($form['formId'] ?? null)) {
                throw new \DomainException('A process cannot be its own parent.');
            }

            $parent = $forms[$parentId] ?? null;

            if (! $parent) {
                throw new \DomainException('Sub process has no parent process.');
            }

            if (! empty($parent['parent_form_id'])) {
                throw new \DomainException('Sub processes can only be one level deep.');
            }

            if (in_array($parentId, $deletedIds, true)) {
                throw new \DomainException('Cannot keep a sub process under a deleted process.');
            }
        }
    }

    public function saveSubData(array $forms, mixed $ppf, int $encoder)
    {
        $prepareForm = $this->prepareDataForSubmission($forms, $ppf, $encoder);
        $this->masterRepo->upsertForm($prepareForm['masterData']);
        $this->defectRepo->saveFormDefect(
            $prepareForm['hfDefect']
        );
        $this->defectRepo->saveFormSmall(
            $prepareForm['hfSmall']
        );
        $this->reworkRepo->saveFormRework(
            $prepareForm['hfRework']
        );
    }

    public function saveGeneralData(array $forms, mixed $ppf, int $encoder)
    {
        // Quantity belongs to the main processes; NG and rework come from every form
        $totalInspect = 0;
        $totalNg      = 0;
        $totalRework  = 0;
        $mainForms    = [];

        foreach ($forms as $form) {
            if (empty($form['parent_form_id'] ?? null)) {
                $mainForms[]   = $form;
                $totalInspect += (int) $form['total_inspect'];
            }
            $totalNg     += (int) $form['TotalNg'];
            $totalRework += (int) $form['TotalRework'];
        }

        if (empty($mainForms)) {
            return;
        }

        $totalGood = $totalInspect - $totalNg;
        $leadForm  = end($mainForms);

        $defectInsp      = [];
        $smallDefectInsp = [];
        $reworkInsp      = [];

        foreach ($forms as $form) {
            $process  = empty($form['parent_form_id'] ?? null) ? ProcessType::HF : ProcessType::HF_SUB;
            $hfId     = $form['hf_id'];
            $inspName = Worker::with('employeeName')
                ->where('作業員CD', $hfId)
                ->first()?->employeeName?->名前;

            $base = [
                'ppfno'       => $ppf,
                'inspectorId' => $hfId,
                'process'     => $process,
                'operation'   => 'HF',
                'updated_by'  => $encoder,
                'updated_at'  => now(),
                'created_at'  => now(),
            ];

            foreach ($form['defects'] ?? [] as $defect) {
                $key = "{$hfId}|{$process}|{$defect['type']}";

                $defectInsp[$key] ??= $base + [
                    'inspName' => $inspName,
                    'defect'   => $defect['type'],
                    'qty'      => 0,
                ];
                $defectInsp[$key]['qty'] += (int) $defect['qty'];
            }

            foreach ($form['smallDefects'] ?? [] as $largeDefect => $smalls) {
                foreach ($smalls as $small) {
                    $key = "{$hfId}|{$process}|{$largeDefect}|{$small['type']}";

                    $smallDefectInsp[$key] ??= $base + [
                        'small_defect' => $small['type'],
                        'large_defect' => $largeDefect,
                        'qty'          => 0,
                    ];
                    $smallDefectInsp[$key]['qty'] += (int) $small['qty'];
                }
            }

            foreach ($form['rework'] ?? [] as $rework) {
                $key = "{$hfId}|{$process}|{$rework['type']}";

                $reworkInsp[$key] ??= $base + [
                    'inspName'      => $inspName,
                    'rework'        => $rework['type'],
                    'qty'           => 0,
                    'total_inspect' => $rework['totalinsp'],
                ];
                $reworkInsp[$key]['qty'] += (int) $rework['qty'];
            }
        }

        $this->masterRepo->upsertGeneralForm([
            'ppfno'         => $ppf,
            'inspectorId'   => $leadForm['hf_id'],
            'total_inspect' => $totalInspect,
            'totalNg'       => $totalNg,
            'totalRework'   => $totalRework,
            'totalGood'     => $totalGood,
            'process'       => ProcessType::HF,
            'operation'     => 'HF',
            'updated_at'    => now(),
            'updated_by'    => $encoder,
            'created_at'    => now(),
        ]);

        $this->defectRepo->saveGeneralDefect(array_values($defectInsp));
        $this->defectRepo->saveGeneralSmall(array_values($smallDefectInsp));
        $this->reworkRepo->saveGeneralRework(array_values($reworkInsp));
    }

    //setting the saving of rework

    public function prepareDataForSubmission(array $forms, int $ppf, int $encoder)
    {
        $masterData = [];
        $hfDefect = [];
        $hfSmall = [];
        $hfRework = [];
        $mergeDefect = [];
        $mergeSmallDefect = [];
        $mergeRework = [];

        foreach ($forms as $form) {
            $isExisting = !empty($form['id'] ?? null);

            $masterData[] = [
                'ppfno'              => $ppf,
                'hfid'               => $form['hf_id'],
                'total_inspect'      => $form['total_inspect'],
                'updated_by'         => $encoder,
                'updated_at'         => now(),
                'formId'             => $form['formId'],
                'parentFormId'       => $form['parent_form_id'] ?? null,
                'goodQty'            => $form['GoodQty'],
                'totalNg'            => $form['TotalNg'],
                'totalRework'        => $form['TotalRework'],
                'forRework'          => $form['ForRework'],
                'finishingProcedure' => $form['finishingProcedure'],
                'process'            => 'HF',
                'remarks'            => $form['Remarks'],
                ...(!$isExisting ? ['created_at' => now()] : []),
            ];

            foreach ($form['defects'] as $defect) {
                $hfDefect[] = [
                    'ppfno'      => $ppf,
                    'hfid'       => $form['hf_id'],
                    'defect'     => $defect['type'],
                    'qty'        => $defect['qty'],
                    'updated_by' => $encoder,
                    'formId'     => $form['formId'],
                ];

                $mergeDefect[$defect['type']] ??= [
                    'type'      => $defect['type'],
                    'qty'       => 0,
                ];

                $mergeDefect[$defect['type']]['qty'] += $defect['qty'];
            }

            foreach ($form['smallDefects'] as $largeDefect => $smallDefect) {
                foreach ($smallDefect as $small) {
                    $hfSmall[] = [
                        'ppfno'       => $ppf,
                        'hfid'        => $form['hf_id'],
                        'largeDefect' => $largeDefect,
                        'smallDefect' => $small['type'],
                        'qty'         => $small['qty'],
                        'updated_by'  => $encoder,
                        'formId'      => $form['formId'],
                    ];
                }
                $mergeSmallDefect[$small['type']] ??= [
                    'type' => $small['type'],
                    'large' => $largeDefect,
                    'qty' => 0
                ];

                $mergeSmallDefect[$small['type']]['qty'] += $small['qty'];
            }

            foreach ($form['rework'] as $rework) {
                $hfRework[] = [
                    'ppfno'         => $ppf,
                    'hfid'          => $rework['hfno'] ?? $form['hf_id'],
                    'rework_type'   => $rework['type'],
                    'qty'           => $rework['qty'],
                    'updated_by'    => $encoder,
                    'total_inspect' => $rework['totalinsp'],
                    'formId'        => $form['formId'],
                ];

                $mergeRework[$rework['type']] ??= [
                    'type' => $rework['type'],
                    'hfno' => $rework['hfno'],
                    'qty' => 0,
                    'total_inspect' => $rework['totalinsp']
                ];

                $mergeRework[$rework['type']]['qty'] += $rework['qty'];
            }
        }

        return [
            'masterData' => $masterData,
            'hfDefect'   => $hfDefect,
            'hfSmall'    => $hfSmall,
            'hfRework'   => $hfRework,
            'mergeDefect' => $mergeDefect,
            'mergeSmallDefect' => $mergeSmallDefect,
            'mergeRework' => $mergeRework
        ];
    }

    public function update2() {}
}
