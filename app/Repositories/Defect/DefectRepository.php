<?php

namespace App\Repositories\Defect;

use App\Models\Defect\DefectMaster;
use App\Models\Defect\SmallDefectMaster;
use App\Models\Forms\Defect;
use App\Models\Forms\SmallDefect;
use App\Models\Inspector\LargeDefect;
use App\Models\Inspector\SmallDefect as InspectorSmallDefect;
use Illuminate\Support\Collection;

class DefectRepository
{
    public function getLargeDefects(): Collection
    {
        return DefectMaster::select('LargeDefect')
            ->distinct()
            ->whereNotNull('LargeDefect')
            ->orderBy('LargeDefect', 'asc')
            ->get();
    }

    /**
     * Distinct list of small defect types belonging to a given large defect.
     */
    public function getSmallDefectsFor(string $largeDefect): Collection
    {
        return SmallDefectMaster::query()
            ->select('SmallDefect')
            ->distinct()
            ->whereNotNull('SmallDefect')
            ->where('LargeDefect', $largeDefect)
            ->orderBy('SmallDefect', 'asc')
            ->get();
    }


    public function deleteDefect(string $formId, string $type): void
    {
        Defect::where('formId', $formId)
            ->where('defect', $type)
            ->delete();
    }

    public function deleteSmallDefect(string $formId, string $largeType, string $smallType): void
    {
        SmallDefect::where('formId', $formId)
            ->where('largeDefect', $largeType)
            ->where('smallDefect', $smallType)
            ->delete();
    }

    // Save section
        public function saveFormDefect(array $forms)
    {
        return Defect::upsert(
            $forms,
            ['ppfno', 'hfid', 'formId', 'defect'],
            ['qty']
        );
    }

    public function saveFormSmall(array $forms)
    {   
        return SmallDefect::upsert(
            $forms,
            ['ppfno', 'hfid', 'largeDefect', 'smallDefect', 'formId'],
            ['qty']
        );
    }

    public function saveGeneralDefect(array $forms){
        return LargeDefect::upsert(
        $forms,
        ['ppfno', 'inspectorId','defect', 'process', 'operation'],
        ['qty', 'updated_at']
        );
    }

    public function saveGeneralSmall(array $forms){
        return InspectorSmallDefect::upsert(
        $forms,
        ['ppfno', 'inspectorId', 'small_defect', 'large_defect', 'process', 'operation'],
        ['qty', 'updated_at']
        );
    }

}
