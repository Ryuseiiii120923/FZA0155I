<?php

namespace App\Action\ProcessRecord;

use App\Models\Auth\Worker;
use App\Models\Auth\WorkerName;
use App\Repositories\Defect\DefectRepository;
use App\Repositories\ProcessRecord\MasterDataPRRepository;
use App\Repositories\Rework\ReworkRepository;
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
        DB::transaction(function () use ($encoder, $forms, $ppf, $pendingDeletes) {
            // dd($forms);
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
        try {
            $totalInspect = 0;
            $totalNg = 0;
            $totalRework = 0;
            $totalGood = 0;
            foreach ($forms as $form) {
                $totalInspect += (int)$form['total_inspect'];
                $totalNg += (int)$form['TotalNg'];
                $totalRework += (int)$form['TotalRework'];
                $totalGood += (int)$form['GoodQty'];
            }
            $mergeDefect = [];
            $mergeSmallDefect = [];
            $mergeRework = [];
            $isExisting = !empty($forms['id'] ?? null);
            //fetch the mergeDefects
            $prepareForm = $this->prepareDataForSubmission($forms, $ppf, $encoder);
            $mergeDefect[] = $prepareForm['mergeDefect'];
            $mergeSmallDefect[] = $prepareForm['mergeSmallDefect'];
            $mergeRework[] = $prepareForm['mergeRework'];

            $defectInsp = [];
            $smallDefectInsp = [];
            $reworkInsp = [];

            foreach ($forms as $form) {
                $inspName = $inspName = Worker::with('employeeName')
                    ->where('作業員CD', $form['hf_id'])
                    ->first()?->employeeName?->名前;
                $generalData = [
                    'ppfno' => $ppf,
                    'inspectorId' => $form['hf_id'],
                    'total_inspect' => $totalInspect,
                    'totalNg' => $totalNg,
                    'totalRework' => $totalRework,
                    'totalGood' => $totalGood,
                    'operation' => 'HF',
                    'updated_at' => now(),
                    'updated_by' => $encoder,
                    ...(!$isExisting ? ['created_at' => now()] : []),
                ];


                foreach ($mergeDefect as $index) {
                    foreach ($index as $def) {
                        $defectInsp[] = [
                            'ppfno' => $ppf,
                            'inspectorId' => $form['hf_id'],
                            'inspName' => $inspName,
                            'defect' => $def['type'],
                            'qty' => $def['qty'],
                            'process' => 'HF',
                            'operation' => 'HF',
                            'updated_by' => $encoder,
                            'updated_at' => now(),
                            ...(!$isExisting ? ['created_at' => now()] : []),
                        ];
                    }
                }
                foreach ($mergeSmallDefect as $indexLarge) {
                    foreach ($indexLarge as $small) {
                        $smallDefectInsp[] = [
                            'ppfno' => $ppf,
                            'inspectorId' => $form['hf_id'],
                            'small_defect' => $small['type'],
                            'large_defect' => $small['large'],
                            'qty' => $small['qty'],
                            'process' => 'HF',
                            'operation' => 'HF',
                            'updated_by' => $encoder,
                            'updated_at' => now(),
                            ...(!$isExisting ? ['created_at' => now()] : []),
                        ];
                    }
                }
                foreach ($mergeRework as $index) {
                    foreach ($index as $rework) {
                        $reworkInsp[] = [
                            'ppfno' => $ppf,
                            'inspectorId' => $form['hf_id'],
                            'inspName' => $inspName,
                            'rework' => $rework['type'],
                            'qty' => $rework['qty'],
                            'total_inspect' => $rework['total_inspect'],
                            'process' => 'HF',
                            'operation' => 'HF',
                            'updated_by' => $encoder,
                            'updated_at' => now(),
                            ...(!$isExisting ? ['created_at' => now()] : []),
                        ];
                    }
                }
            }

            $this->masterRepo->upsertGeneralForm($generalData);
            $this->defectRepo->saveGeneralDefect($defectInsp);
            $this->defectRepo->saveGeneralSmall($smallDefectInsp);
            $this->reworkRepo->saveGeneralRework($reworkInsp);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
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
}
