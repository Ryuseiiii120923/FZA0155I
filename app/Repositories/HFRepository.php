<?php

namespace App\Repositories;

use App\Models\AddDefect;
use App\Models\HFDetails\HFID;
use App\Models\HFDetails\HFName;

class HFRepository
{
    public function getWorkerId(int $id)
    {
        return HFID::where('作業員CD', $id)->first();
    }
    public function getWorkerIds(array $inspectorIds)
    {
        return HFID::whereIn('作業員CD', $inspectorIds)
            ->pluck('社員CD', '作業員CD');
    }

    public function getHfName($id)
    {
        return HFName::where('社員CD', $id)->first();
    }
    public function getHfNamesByWorkerIds($workerIds)
    {
        return HFName::whereIn('社員CD', $workerIds)
            ->pluck('名前', '社員CD');
    }

    public function getHfPerPPF($ppf)
    {
        return AddDefect::select('HFNo1', 'HFNo2', 'HFNo3', 'HFNo4', 'HFNo5')->where('PPFNo', $ppf)->first();
    }
}
