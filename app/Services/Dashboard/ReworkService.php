<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\ReworkRepository;

class ReworkService{
    protected $repo;

    public function __construct(ReworkRepository $reworkRepository){
        $this->repo = $reworkRepository;
    }

    public function fetchRework(int $ppf){
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
            'qty'         => (int) $item->qty,
            'dateEncode'   => $item->created_at
        ];
    })->values();

    $total = $reworks->sum('qty');

    $payload = $reworks->map(function ($r) {
        return [
            'type'      => $r['type'],
            'qty'      => $r['qty'],
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