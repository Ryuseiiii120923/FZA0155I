<?php

namespace App\Services\Helper;

use App\Models\mainDb\VICheck;
use App\Repositories\Contracts\PcMasterRepositoryInterface;
use App\Repositories\SaveRepository;
use App\Services\ProcessRecord\ProcessService\ProcessService;
use App\Services\ProcessRecord\ProcessService\VICheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DashboardSaveService
{
    public function __construct(
        private readonly PcMasterRepositoryInterface $pcMasters,
    ) {
    }

    public function save(int $ppf, array $draft, string $deviceUuid): void
    {
        $header = $draft['header'];
        $defects = $draft['defects']['defects'];
        $smallDefects = $draft['defects']['smallDefects'];
        $goodNg = $draft['goodNg'];
        $otherDetails = $draft['otherDetails'];
        $inspection = $draft['inspectors']['inspections'][0] ?? [];

        if (empty($otherDetails['hfGroup'])) {
            throw new InvalidArgumentException('HF group is missing.');
        }

        if (empty($otherDetails['hfDate'])) {
            throw new InvalidArgumentException('HF date is missing.');
        }

        $pcMaster = $this->pcMasters->findByUuid($deviceUuid);

        if ($pcMaster === null) {
            throw new InvalidArgumentException("Unrecognized device [{$deviceUuid}] — cannot save without a registered host name.");
        }

        $deviceName = $pcMaster->HostName;

        try {
            DB::transaction(function () use ($ppf, $header, $defects, $smallDefects, $goodNg, $otherDetails, $inspection, $deviceName) {
                app(SaveRepository::class)->deleteMain($ppf);
                app(SaveRepository::class)->saveMain(
                    ppf: $ppf,
                    header: $header,
                    defects: $defects,
                    smallDefects: $smallDefects,
                    goodNg: $goodNg,
                    otherDetails: $otherDetails,
                    inspection: $inspection,
                );
                app(VICheckService::class)->handle($header['action'], $ppf, $header['partno'], $header['lotno'], $goodNg['goodqty'], $otherDetails['encoder'], $deviceName, $header['moldno']);
                $data = [
                    'ppf' => $ppf,
                    'partNo' => $header['partno'],
                    'goodQty' => $goodNg['goodqty'],
                    'moldQty' => $header['totalInspected'],
                    'lotNo' => $header['lotno'],
                    'inspectionDate' => $otherDetails['hfDate'],
                    'encoder' => $otherDetails['encoder'],
                    'action' => $header['action']
                ];
                app(ProcessService::class)->process($data);
            });
        } catch (\Throwable $e) {
            Log::error('Error saving PPF draft', [
                'ppf'     => $ppf,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function delete(int $ppf)
    {
        app(SaveRepository::class)->deleteMain($ppf);
    }
}