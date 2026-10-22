<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\GoodNgRepository;
use App\Repositories\Dashboard\PPFRepository;

class GoodNgService
{
    public function __construct(protected GoodNgRepository $repo) {}

    public function getBaseQty(int $ppf): array
    {
        $result = $this->repo->getBaseQty($ppf);
        $check = app(PPFRepository::class)->fetchHeadData($ppf);

        return [
            'expct' => (int) ($check->未仕上 ?? 0),
            'goodqty'   => (int) $result['goodQty'],
            'totalNG'   => (int) $result['totalNG'],
            'lackqty'   => (int) $result['lackQty'],
            'reworkqty' => (int) $result['reworkQty'],
            'sampleqty' => (int) $result['sampleQty'],
        ];
    }

    public function computeGoodNg(
        array $base,
        int $excssqty = 0,
        int $lackqty = 0,
        int $reworkqty = 0,
        int $sampleqty = 0,
    ): array {
       
        $goodqty = $base['expct']
            + $excssqty
            - $lackqty
            - $reworkqty
            - $sampleqty;

        $denominator = $goodqty + $base['totalNG'];
        $ngratioqty  = $denominator === 0
            ? 0
            : number_format(($base['totalNG'] / $denominator) * 100, 2);

        return [
            'goodqty'    => $goodqty,
            'ngratioqty' => $ngratioqty,
        ];
    }
}