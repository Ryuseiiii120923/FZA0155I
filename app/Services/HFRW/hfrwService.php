<?php

namespace App\Services\HFRW;

use App\Repositories\HFRW\hfrwRepository;

class hfrwService
{
    public function __construct(protected hfrwRepository $repo) {}

    // ----------- Initialize Fetching Section -----------------
    public function fetchPendingRework(?string $search = null, int $perPage = 5)
    {
        $reworks = $this->repo->fetchPendingRework($search, $perPage);

        return $reworks->through(function ($rework) {
            return [
                'ppfno'    => $rework->ppfno,
                'reworkNo' => $rework->reworkNo,
                'qty'      => $rework->qty,
                'status'   => (int)$rework->flgDone === 1
                    ? 'Confirmed'
                    : 'Pending',
            ];
        });
    }

    // ----------- End of Fetching Section -----------------

    // ----------- Fetching Section -----------------

    public function fetchReworkDetails(int $ppf = 0, int $reworkNo = 0, int $inspectorId = 0)
    {
        $forms = [];
        $defectNg = [];

        $hfrework = $this->repo->fetchReworkDetails($ppf, $reworkNo, $inspectorId);

        if (!$hfrework) {
            return ['error' => 'No rework details found for the given PPF.'];
        }

        foreach ($hfrework as $h) {
            $defectsRaw = $this->repo->fetchDefects($ppf, $h->inspect_REC);
            $smallDefectsRaw = $this->repo->fetchSmallDefects($ppf, $h->inspect_REC);

            $defects = $defectsRaw
                ->groupBy(fn($d) => strtolower(trim($d->defect)))
                ->map(function ($group) {
                    $first = $group->first();
                    return [
                        'id' => $first->RECNO,
                        'type' => $first->defect,
                        'qty' => $group->sum(fn($d) => $d->qty ?? 1),
                    ];
                })
                ->values()
                ->toArray();


            $defectNg[$h->hfid] = collect($defects)->sum('qty');

            $small = $smallDefectsRaw
                ->groupBy('large_defect')
                ->mapWithKeys(function ($group, $largeDefect) {
                    return [
                        $largeDefect => collect($group)->map(fn($s) => [
                            'id' => $s->RECNO,
                            'type' => $s->small_defect,
                            'qty' => $s->qty ?? 0,
                        ])->toArray()
                    ];
                })
                ->toArray();

            $selectedLarge = array_key_first($small);



            $forms[$h->inspect_REC] = [
                'hf_id' => $h->hfid,
                'total_inspect' => $h->total_inspect,
                'defects' => $defects,
                'smallDefects' => $small,
                'ppfno' => (int)$ppf,
                'open' => true,
                'inspect_REC' => $h->inspect_REC,
                'selectedLargeDefect' => $selectedLarge,
                'created_at' => $h->created_at,
                'updated_at' => $h->updated_at,
            ];
        }
        return compact('forms', 'defectNg');
    }

    // ----------- End of Fetching Section -----------------
    public function buildEmptyForm($ppf)
    {
        return [
            'hf_id'         => '',
            'hf_name'       => '',
            'total_inspect' => 0,
            'defects'       => [],
            'smallDefects'  => [],
            'defectNg'      => 0,
            'reworkNg'      => 0,
            'GoodQty'       => 0,
            'TotalNg'       => 0,
            'status'        => 'Pending',
            'ppfno'         => (int) $ppf,
            'open'          => false,
            'inspect_REC' => uniqid(),
            'Process'       => 'HFRW',
        ];
    }

    public function calcGoodQty(array $form, ?float $defectNgOverride, ?float $reworkNgOverride): array
    {
        $defectQty = $defectNgOverride ?? collect($form['defects'] ?? [])->sum('qty');
        $reworkQty = $reworkNgOverride ?? collect($form['rework'] ?? [])->sum('quan');
        $totalNg   = $defectQty + $reworkQty;

        return [
            'goodQty'   => ($form['total_inspect'] ?? 0) - $totalNg - $reworkQty,
            'totalNg'   => $totalNg,
            'reworkQty' => $reworkQty,
        ];
    }

    public function prepareFormsForSave(array $forms, ?int $selectedPpf): array
    {
        return collect($forms)
            ->filter(fn($form) => ($form['ppfno'] ?? null) == $selectedPpf)
            ->map(function ($form) {
                $defectNg = collect($form['defects'] ?? [])->sum('qty');
                $form['GoodQty'] = ($form['total_inspect'] ?? 0) - $defectNg;
                return $form;
            })
            ->values()
            ->toArray();
    }
}
