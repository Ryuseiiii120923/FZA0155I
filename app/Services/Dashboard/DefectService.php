<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DefectRepository;

class DefectService
{

    protected $repo;
    public function __construct(DefectRepository $defectRepository)
    {
        $this->repo = $defectRepository;
    }
    public function fetchDefect(int $ppf)
    {
        $rows = $this->repo->getDefectsGrouped($ppf)
            ->filter(fn($row) => (int)$row->total_qty > 0);
        $allSmalls = $this->repo->getSmallDefectsForPpf($ppf);
        // dd($allSmalls, $rows);

        $defects = $rows->map(fn($row) => [
            'operatorid' => $row->inspectorId,
            'operatorname' => $row->inspName,
            'type' => $row->defect,
            'qty' => (int)$row->total_qty,
            'dateEncode' => $row->latest_date,
            'process' => $row->process
        ]);

        $smallDefects = [];

        foreach ($rows as $row) {
            $key = "{$row->defect}||{$row->process}||{$row->inspectorId}";
            foreach ($allSmalls->get($key, []) as $s) {
                $smallDefects[$row->defect][$row->process][$row->inspectorId][] = [
                    'type' => $s->small_defect,
                    'qty'  => (int)$s->total_qty,
                ];
            }
        }

        $defectPayload = $defects
            ->groupBy(fn($d) => strtolower(trim($d['type'])) . '_' . $d['process'])
            ->map(fn($group) => [
                'newDefect' => $group->first()['type'],
                'newQuan' => $group->sum('qty'),
                'process' => $group->first()['process'],
                'action' => ''
            ])->values();

        return [
            'defects' => $defects->all(),
            'smallDefects' => $smallDefects,
            'payload' => $defectPayload->all(),
            'totalQty' => $defects->sum('qty'),
            'inspectors' => $defects->pluck('operatorid')->unique()->values(),
            'last' => $defects->last()
        ];
    }
}
