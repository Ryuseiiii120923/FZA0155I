<?php

namespace App\Repositories\Rework;

use App\Models\Forms\Rework;
use App\Models\Inspector\Rework as InspectorRework;

class ReworkRepository
{
    public function saveFormRework(array $form)
    {
        return Rework::upsert(
            $form,
            ['ppfno', 'hfid', 'formId', 'rework_type'],
            ['qty', 'total_inspect']
        );
    }

    public function deleteRework(string $formId, string $hfno, string $type)
    {
        return Rework::where('formId', $formId)
            ->where('hfno', $hfno)
            ->where('rework_type', $type)
            ->delete();
    }

    public function saveGeneralRework(array $form){
        return InspectorRework::upsert(
        $form,
        ['ppfno', 'inspectorId', 'rework', 'process', 'operation'],
        ['qty', 'updated_at']
        );
    }
}
