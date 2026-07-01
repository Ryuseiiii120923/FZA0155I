<?php

namespace App\Services\Dashboard;

use App\Models\AddDefect;
use App\Models\Auth\Worker;
use App\Models\Auth\WorkerName;
use App\Models\HFGroup;
use App\Repositories\Dashboard\OtherDetailsRepository;

class OtherDetailsService
{
    protected $repo;
    public function __construct(protected OtherDetailsRepository $repository)
    {
        $this->repo = $repository;
    }

    public function getAllFNMachine()
    {
        return $this->repo->fetchHFMachine();
    }

    public function getOtherDetails(int $ppf, int $currentEncoder)
    {
        $encoder = AddDefect::where('PPFNo', $ppf)->value('Encoder');
        $encoder = $encoder ?? $currentEncoder;
        $name = WorkerName::where('社員CD', $encoder)->value('名前');
        $hfGroup = HFGroup::where('ppfno', $ppf)->where('operation', 'HF')->value('hf_group');
        $result = $this->repo->fetchDetails($ppf);
        return ([
            'autoMachines' => $result->FinMachine ?? ""
                ? explode(',', $result->FinMachine)
                : [],

            'details' => $result->Details ?? "",
            'updateDate' => $result->DateEncode ?? "",
            'hfDate' => $result->HFDate ?? ""
                ? \Carbon\Carbon::parse($result->HFDate)->format('Y-m-d')
                : '',
            'encoder' => $encoder ?? "",
            'name' => $name ?? "",
            'hfGroup' => $hfGroup ?? ""
        ]);
    }
}
