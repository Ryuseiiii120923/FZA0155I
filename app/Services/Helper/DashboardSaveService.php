<?php

namespace App\Services\Helper;

use App\Repositories\SaveRepository;
use App\Services\ProcessRecord\ProcessService\ProcessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardSaveService
{
    public function save(int $ppf, array $draft): void
{
    $header = $draft['header'];
    $defects = $draft['defects']['defects'];
    $smallDefects = $draft['defects']['smallDefects'];
    $goodNg = $draft['goodNg'];
    $otherDetails = $draft['otherDetails'];
    $inspection = $draft['inspectors']['inspections'][0] ?? [];

    try {
        DB::transaction(function () use ($ppf, $header, $defects, $smallDefects, $goodNg, $otherDetails, $inspection) {
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

    public function delete(int $ppf){
        app(SaveRepository::class)->deleteMain($ppf);
    }
}