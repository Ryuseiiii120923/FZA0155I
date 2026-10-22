<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\ReworkRepository;
use App\Repositories\ProcessRecord\SubProcessRepository;
use App\Support\ProcessType;
use App\Support\SubProcessNester;

class ReworkService
{
    protected $repo;

    public function __construct(ReworkRepository $reworkRepository)
    {
        $this->repo = $reworkRepository;
    }

    public function fetchRework(int $ppf)
    {
        $rows = $this->repo->fetchRework($ppf);

        if ($rows->isEmpty()) {
            return [
                'reworks' => [],
                'total' => 0
            ];
        }

        $reworks = $rows->map(function ($item) {
            return [
                'operatorid'   => $item->inspectorId,
                'operatorname' => $item->inspName,
                'totalinsp'    => $item->total_inspect,
                'type'         => $item->rework,
                'qty'          => (int) $item->qty,
                'dateEncode'   => $item->created_at,
                'process'      => $item->process ?? null,
                'isSub'        => ProcessType::isSub($item->process ?? null),
            ];
        })->values();

        $reworks = SubProcessNester::nest(
            $reworks,
            app(SubProcessRepository::class)->parentHfidBySubHfid($ppf)
        );

        $total = $reworks->sum('qty');

        $payload = $reworks->map(function ($r) {
            return [
                'type'      => $r['type'],
                'qty'       => $r['qty'],
                'totalinsp' => $r['totalinsp'],
                'action'    => 'initial'
            ];
        })->values();

        return [
            'reworks' => $reworks->all(),
            'payload' => $payload->all(),
            'total'   => $total
        ];
    }
}
