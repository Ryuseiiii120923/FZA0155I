<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\GoodNgRepository;

class GoodNgService
{
    public function __construct(protected GoodNgRepository $repo) {}

    public function getBaseQty(int $ppf): array
    {
        $result = $this->repo->getBaseQty($ppf);

        return [
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
        $goodqty = $base['goodqty']
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