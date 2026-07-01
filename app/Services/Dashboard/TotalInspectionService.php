<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\TotalInspectionRepository;

class TotalInspectionService
{
    protected $repo;
    public function __construct(protected TotalInspectionRepository $repository)
    {
        $this->repo = $repository;
    }

    public function fetchTotalInspection(int $ppf)
    {
        $result = $this->repo->totalInspection($ppf);
        return $result->map(function ($result) {
            return [
                'hfid' => $result->hfid,
                'hfname' => $result->worker?->employeeName?->名前,
                'totalInspect' => $result->total_inspect,
                'dateEncode' => $result->updated_at,
            ];
        })->all();
    }
}
