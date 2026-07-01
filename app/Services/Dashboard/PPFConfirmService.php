<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\PPFConfirmRepository;

class PPFConfirmService
{
    public function __construct(protected PPFConfirmRepository $repository)
    {
    }

    public function fetchData(?string $search = null, int $perPage = 5)
    {
        return $this->repository->fetchData($search, $perPage);
    }

    public function fetchSavedData(?string $search = null, int $perPage = 5){
        return $this->repository->fetchSavedData($search, $perPage);
    }
}