<?php

namespace App\Services\ProcessRecord;

use App\Repositories\ProcessRecord\ProcessRecordRepository;

class ProcessRecordService
{
    protected $repo;
    public function __construct(ProcessRecordRepository $repo)
    {
        $this->repo = $repo;
    }

    public function fetchProcessRecordData(int $inspectorId, $search = null, int $perPage = 5)
    {
        return $this->repo->getProcessRecordData($inspectorId, $perPage, $search);
    }

    public function fetchForms(int $ppf, int $encoder)
    {
        $forms = [];
        $defectNg = [];
        $reworkNg = [];

        $hfRecords = $this->repo->getForms($ppf, $encoder);

        if ($hfRecords->isEmpty()) {
            return ['error' => 'The forms for' . $ppf . 'is empty'];
        }

        $subRecords = $this->repo->getSubForms($ppf, $encoder);

        foreach ($hfRecords->concat($subRecords) as $record) {
            $built = $this->buildForm($record, $ppf, $encoder);

            $forms[$record->formId]    = $built['form'];
            $defectNg[$record->formId] = $built['defectNg'];
            $reworkNg[$record->formId] = $built['reworkNg'];
        }

        return compact('forms', 'defectNg', 'reworkNg');
    }

    private function buildForm($record, int $ppf, int $encoder): array
    {
        $defectRaw = $this->repo->getGroupedDefects($ppf, $encoder, $record->formId);

        $operatorDefect = $defectRaw
            ->groupBy(fn($d) => strtolower(trim($d->defect)))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'id' => $first->id,
                    'type' => $first->defect,
                    'qty' => $group->sum(fn($d) => $d->qty ?? 1)
                ];
            })
            ->values()
            ->toArray();

        $smDefectRaw = $this->repo->getGroupedSmall($ppf, $encoder, $record->formId);

        $operatorSmDefect = $smDefectRaw
            ->groupBy('largeDefect')
            ->mapWithKeys(function ($group, $largeDefect) {
                return [
                    $largeDefect => collect($group)->map(fn($s) => [
                        'id' => $s->id,
                        'type' => $s->smallDefect,
                        'qty' => $s->qty ?? 0
                    ])
                ];
            })
            ->toArray();

        $selectedLarge = array_key_first($operatorSmDefect);

        $reworkRaw = $this->repo->getGroupedReworks($ppf, $encoder, $record->formId);

        $operatorRework = $reworkRaw->map(fn($r) => [
            'id' => $r->id,
            'hfno' => $r->hfid,
            'totalinsp' => $r->total_inspect,
            'type' => $r->rework_type,
            'qty' => $r->qty ?? 0
        ])
            ->toArray();

        $parentFormId = $record->parentFormId ?? null;

        return [
            'defectNg' => collect($operatorDefect)->sum('qty'),
            'reworkNg' => collect($operatorRework)->sum('qty'),
            'form' => [
                'id' => $record->id,
                'formId' => $record->formId,
                'parent_form_id' => $parentFormId,
                'hf_id' => $record->hfid,
                'ppfno' => $record->ppfno,
                'total_inspect' => $record->total_inspect,
                'finishingProcedure' => $record->finishingProcedure,
                'open' => true,
                'defects' => $operatorDefect,
                'rework' => $operatorRework,
                'smallDefects' => $operatorSmDefect,
                'created_at' => $record->created_at ?? null,
                'updated_at' => $record->updated_at ?? null,
                'selectedLargeDefect' => $selectedLarge,
                'ForRework' => (bool) $record->forRework,
                'Remarks' => $record->remarks,
                'Process' => $parentFormId ? 'Sub Process' : '100% VI',
            ],
        ];
    }
}
