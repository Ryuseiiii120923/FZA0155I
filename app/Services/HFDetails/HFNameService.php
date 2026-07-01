<?php

namespace App\Services\HFDetails;

use App\Repositories\HFRepository;

class HFNameService
{
    protected $repo;
    public function __construct(HFRepository $repository)
    {
        $this->repo = $repository;
    }

    public function getHfName(array $inspectorID): array
    {
        $workerMap = $this->repo->getWorkerIds($inspectorID);
        $names = $this->repo->getHfNamesByWorkerIds($workerMap->values()->toArray());
        return collect($workerMap)
            ->mapWithKeys(function ($workerId, $inspectorId) use ($names) {
                return [
                    $inspectorId => $names[$workerId] ?? ''
                ];
            })
            ->toArray();
    }
}
