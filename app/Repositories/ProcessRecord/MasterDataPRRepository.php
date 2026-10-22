<?php

namespace App\Repositories\ProcessRecord;

use App\Models\Forms\Defect;
use App\Models\Forms\MasterDataForms;
use App\Models\Forms\Rework;
use App\Models\Forms\SmallDefect;
use App\Models\Forms\SubMasterDataForms;
use App\Models\Inspector\MasterData;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MasterDataPRRepository
{
    public function upsertForm(array $forms)
    {

        $forms = array_map(fn(array $row) => $row + ['created_at' => now()], $forms);

        [$subRows, $mainRows] = collect($forms)->partition(
            fn(array $row) => ! empty($row['parentFormId'] ?? null)
        );

        if ($mainRows->isNotEmpty()) {
            MasterDataForms::upsert(
                $mainRows->map(fn(array $row) => Arr::except($row, ['parentFormId']))->values()->all(),
                ['hfid', 'ppfno', 'updated_by', 'formId'],
                ['total_inspect', 'goodQty', 'totalNg', 'totalRework', 'remarks', 'updated_at', 'finishingProcedure']
            );
        }

        if ($subRows->isNotEmpty()) {
            SubMasterDataForms::upsert(
                $subRows->values()->all(),
                ['hfid', 'ppfno', 'updated_by', 'formId'],
                ['total_inspect', 'goodQty', 'totalNg', 'totalRework', 'remarks', 'updated_at', 'finishingProcedure', 'parentFormId']
            );
        }
    }

    public function upsertGeneralForm(array $form)
    {
        return MasterData::upsert(
            $form,
            ['inspectorId', 'ppfno', 'operation', 'updated_by'],
            ['total_inspect', 'totalNg', 'totalRework', 'totalGood', 'updated_at']
        );
    }

    public function deleteFormCascade(string $formId): void
    {
        DB::transaction(function () use ($formId) {
            $subIds  = SubMasterDataForms::where('parentFormId', $formId)->pluck('formId')->all();
            $formIds = [$formId, ...$subIds];

            SmallDefect::whereIn('formId', $formIds)->delete();
            Defect::whereIn('formId', $formIds)->delete();
            Rework::whereIn('formId', $formIds)->delete();

            SubMasterDataForms::whereIn('formId', $formIds)->delete();
            MasterDataForms::where('formId', $formId)->delete();
        });
    }
}
