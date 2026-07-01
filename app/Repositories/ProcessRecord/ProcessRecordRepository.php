<?php

namespace App\Repositories\ProcessRecord;

use App\Models\Forms\Defect;
use App\Models\Forms\MasterDataForms;
use App\Models\Forms\Rework;
use App\Models\Forms\SmallDefect;
use App\Models\Inspector\LargeDefect;
use App\Models\Inspector\MasterData;
use App\Models\Inspector\Rework as InspectorRework;
use App\Models\Inspector\SmallDefect as InspectorSmallDefect;
use Illuminate\Support\Facades\DB;

class ProcessRecordRepository
{
    public function getProcessRecordData(int $inspectorId, int $perPage = 5, $search = null){
        return MasterData::where('inspectorId', $inspectorId)
        ->when($search, function ($query, $search) {
            $query->where('ppfno', 'like', "%{$search}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    public function getForms(int $ppf, int $encoder){
        return MasterDataForms::where('ppfno', $ppf)
        ->where('updated_by', $encoder)
        ->get();
    }

    public function getGroupedDefects(int $ppf, int $encoder, mixed $formId){
        return Defect::where('ppfno', $ppf)
        ->where('updated_by', $encoder)
        ->where('formId', $formId)
        ->get();
    }

    public function getGroupedSmall(int $ppf, int $encoder, mixed $formId){
         return SmallDefect::where('ppfno', $ppf)
         ->where('updated_by', $encoder)
         ->where('formId', $formId)
         ->whereNotNull('largeDefect')
         ->get();
    }

    public function getGroupedReworks(int $ppf, int $encoder, mixed $formId){
        return Rework::where('ppfno', $ppf)
        ->where('updated_by', $encoder)
        ->where('formId', $formId)
        ->get();
    }

    public function deletePPF(int $ppf, int $encoder){
         DB::transaction(function () use ($ppf, $encoder) {
            Defect::where('ppfno', $ppf)->where('updated_by', $encoder)->delete();
            MasterDataForms::where('ppfno', $ppf)->where('updated_by', $encoder)->delete();
            Rework::where('ppfno',$ppf)->where('updated_by', $encoder)->delete();
            SmallDefect::where('ppfno', $ppf)->where('updated_by', $encoder)->delete();
            //General Data
            LargeDefect::where('ppfno', $ppf)->where('inspectorId', $encoder)->delete();
            MasterData::where('ppfno', $ppf)->where('inspectorId', $encoder)->delete();
            InspectorRework::where('ppfno',$ppf)->where('inspectorId',$encoder)->delete();
            InspectorSmallDefect::where('ppfno', $ppf)->where('inspectorId', $encoder)->delete();
         });

    }
}
