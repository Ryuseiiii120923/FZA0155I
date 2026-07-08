<?php

namespace App\Repositories\ProcessRecord;

use App\Models\Forms\Defect;
use App\Models\Forms\MasterDataForms;
use App\Models\Forms\Rework;
use App\Models\Forms\SmallDefect;
use App\Models\Inspector\MasterData;
use Illuminate\Support\Facades\DB;

class MasterDataPRRepository
{
    public function upsertForm(array $form)
    {
        $saveMaster = MasterDataForms::upsert(
            $form,
            ['hfid', 'ppfno', 'updated_by', 'formId'],
            ['total_inspect', 'goodQty', 'totalNg','totalRework', 'remarks', 'updated_at', 'finishingProcedure']
        );

        return $saveMaster;
    }

    public function upsertGeneralForm(array $form){
        return MasterData::upsert(
            $form,
            ['inspectorId', 'ppfno','operation','updated_by'],
            ['total_inspect', 'totalNg', 'totalRework', 'totalGood','updated_at']);
    }

    public function deleteFormCascade(string $formId): void
    {
        DB::transaction(function () use ($formId) {
            SmallDefect::where('formId', $formId)->delete();
            Defect::where('formId', $formId)->delete();
            Rework::where('formId', $formId)->delete();
            MasterDataForms::where('formId', $formId)->delete();
        });
    }
}
