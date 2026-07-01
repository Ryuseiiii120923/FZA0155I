<?php

namespace App\Services\Helper;

use App\Repositories\SaveRepository;
use Illuminate\Support\Facades\DB;

class DashboardSaveService
{
    public function save(int $ppf, array $draft): void
    {

        $header = $draft['header'];
        $defects = $draft['defects']['defects'];
        $goodNg = $draft['goodNg'];
        $otherDetails = $draft['otherDetails'];
        $inspection = $draft['inspectors']['inspections'][0] ?? [];

        DB::transaction(function () use ($ppf, $header, $defects, $goodNg, $otherDetails, $inspection) {
            app(SaveRepository::class)->deleteMain($ppf);
            app(SaveRepository::class)->saveMain(
                ppf: $ppf,
                header: $header,
                defects: $defects,
                goodNg: $goodNg,
                otherDetails: $otherDetails,
                inspection: $inspection,
            );
        });
    }

    public function delete(int $ppf){
        app(SaveRepository::class)->deleteMain($ppf);
    }
}