<?php

namespace App\Action\ProcessRecord;

use App\Models\Auth\WorkerName;
use App\Repositories\Defect\DefectRepository;
use App\Repositories\ProcessRecord\MasterDataPRRepository;
use App\Repositories\ProcessRecord\ProcessRecordRepository;
use App\Repositories\Rework\ReworkRepository;
use App\Services\ProcessRecord\ProcessRecordRefresher;
use Illuminate\Support\Facades\DB;

class DeletePpfAction{
     public function __construct(
        private ProcessRecordRepository $processRecordRepository,
        private ProcessRecordRefresher $recordRefresher,
    ) {}
    public function execute(int $ppf, int $encoder){
        
        $this->processRecordRepository->deletePPF($ppf, $encoder);
        $this->recordRefresher->refreshAfterResponse($ppf);
    }
}