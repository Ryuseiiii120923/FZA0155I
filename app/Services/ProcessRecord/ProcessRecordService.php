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

        foreach ($hfRecords as $record) {
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

            $defectNg[$record->hfid] = collect($operatorDefect)->sum('qty');

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

            $reworkRaw  = $this->repo->getGroupedReworks($ppf, $encoder, $record->formId);

            $operatorRework = $reworkRaw->map(fn($r) => [
                'id' => $r->id,
                'hfno' => $r-> hfid,
                'totalinsp' => $r->total_inspect,
                'type' => $r->rework_type,
                'qty' => $r->qty ?? 0
            ])
            ->toArray();
            $reworkNg[$record->hfid] = collect($operatorRework)->sum('qty');
            $forms[$record->formId] = [
                'id' => $record->id,
                'formId' => $record->formId,
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
                'Remarks' => $record->remarks
            ];
        }
        return compact('forms', 'defectNg', 'reworkNg');
    }
}
