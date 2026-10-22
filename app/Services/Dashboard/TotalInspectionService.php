<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\TotalInspectionRepository;
use App\Repositories\ProcessRecord\SubProcessRepository;

class TotalInspectionService
{
    protected $repo;

    public function __construct(
        protected TotalInspectionRepository $repository,
        protected SubProcessRepository $subRepo,
    ) {
        $this->repo = $repository;
    }

    public function fetchTotalInspection(int $ppf)
    {
        $subsByParent = $this->subRepo->forPpf($ppf)->groupBy('parentFormId');
        $parentHfid   = $this->subRepo->parentHfidMap($ppf);
        $used         = [];
        $rows         = [];

        foreach ($this->repo->totalInspection($ppf) as $main) {
            $rows[] = $this->row($main);

            // Sub processes sit right under their parent
            foreach ($subsByParent->get($main->formId ?? '', []) as $sub) {
                $rows[] = $this->row($sub, true, $main->hfid);
                $used[$sub->formId] = true;
            }
        }

        // Subs whose parent row isn't in the list above still get shown, after the main rows
        foreach ($subsByParent as $parentFormId => $subs) {
            foreach ($subs as $sub) {
                if (! isset($used[$sub->formId])) {
                    $rows[] = $this->row($sub, true, $parentHfid[$parentFormId] ?? null);
                }
            }
        }

        return $rows;
    }

    private function row($record, bool $isSub = false, ?string $parentHfid = null): array
    {
        return [
            'formId'       => $record->formId ?? null,
            'hfid'         => $record->hfid,
            'hfname'       => $record->worker?->employeeName?->名前,
            'totalInspect' => $record->total_inspect,
            'dateEncode'   => $record->updated_at,
            'isSub'        => $isSub,
            'parentHfid'   => $parentHfid,
        ];
    }
}