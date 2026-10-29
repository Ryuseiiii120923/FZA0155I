<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use App\Models\Inspector\MasterData;
use App\Models\PPFHeader\CheckHF;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PPFConfirmRepository
{
    private const VERSION_KEY = 'ppf_confirm:version';
    private const TTL_SECONDS = 60;
    private const MAX_ROWS = 500;

    // Gawing true PAGKATAPOS patakbuhin ang SQL para sa PPFNoStr column at index
    private const USE_PPFNOSTR_COLUMN = true;

    public function fetchData(?string $search = null, int $perPage = 5)
    {
        return $this->cachedPaginator(
            'pending',
            $search,
            $perPage,
            fn() => $this->queryPending($search, $perPage)
        );
    }

    public function fetchSavedData(?string $search = null, int $perPage = 20)
    {
        return $this->cachedPaginator(
            'saved',
            $search,
            $perPage,
            fn() => $this->querySaved($search, $perPage)
        );
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }

    private function cachedPaginator(string $type, ?string $search, int $perPage, Closure $query): LengthAwarePaginator
    {
        $page   = Paginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;

        // Lampas na sa MAX_ROWS, huwag nang mag-query
        if ($offset >= self::MAX_ROWS) {
            return new LengthAwarePaginator(
                collect(),
                self::MAX_ROWS,
                $perPage,
                $page,
                ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
            );
        }

        $remaining = self::MAX_ROWS - $offset;

        $payload = Cache::remember(
            $this->cacheKey($type, $search, $perPage),
            self::TTL_SECONDS,
            function () use ($query, $remaining) {
                $paginated = $query();

                return [
                    'total' => min($paginated->total(), self::MAX_ROWS),
                    'items' => $paginated->getCollection()
                        ->take($remaining)
                        ->map(fn($item) => [
                            'ppfno'         => $item->ppfno,
                            'total_inspect' => $item->total_inspect,
                            'updated_at'    => (string) $item->updated_at,
                            'expct'         => $item->expct,
                        ])->all(),
                ];
            }
        );

        return new LengthAwarePaginator(
            collect($payload['items'])->map(fn($row) => (object) $row),
            $payload['total'],
            $perPage,
            $page,
            [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    private function cacheKey(string $type, ?string $search, int $perPage): string
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn() => (string) Str::uuid());
        $page    = Paginator::resolveCurrentPage();
        $search  = trim((string) $search);

        return "ppf_confirm:{$version}:{$type}:" . md5("{$search}|{$perPage}|{$page}");
    }

    private function queryPending(?string $search, int $perPage)
    {
        $paginated = MasterData::select('ppfno')
            ->Where('operation', 'HF')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->whereNotExists(function ($query) {
                $column = self::USE_PPFNOSTR_COLUMN
                    ? 'FF.dbo.HFDefect.PPFNoStr'
                    : 'CAST(CAST(FF.dbo.HFDefect.PPFNo AS BIGINT) AS NVARCHAR(50))';

                $query->select(DB::raw(1))
                    ->from('FF.dbo.HFDefect')
                    ->whereRaw("{$column} = PRecord.dbo.inspector_pr.ppfno");
            })
            ->when($search, function ($query) use ($search) {
                $query->where('ppfno', 'like', "%{$search}%");
            })
            ->groupBy('ppfno')
            ->orderByRaw('MAX(updated_at) DESC')
            ->paginate($perPage);

        return $this->attachExpct($paginated);
    }

    private function querySaved(?string $search, int $perPage)
    {
        // Tinanggal ang with('finalInspection'): hindi ito kasama sa cache payload
        $paginated = AddDefect::selectRaw('PPFNo as ppfno')
            ->selectRaw('MAX(Total) as total_inspect')
            ->selectRaw('MAX(DateEncode) as updated_at')
            ->when($search, function ($q) use ($search) {
                $q->whereRaw(
                    "CAST(CAST(PPFNo AS BIGINT) AS NVARCHAR(50)) LIKE ?",
                    ["%{$search}%"]
                );
            })
            ->groupBy('PPFNo')
            ->orderByRaw('MAX(DateEncode) DESC')
            ->paginate($perPage);

        return $this->attachExpct($paginated);
    }

    /**
     * Pareho ang lookup ng expct para sa pending at saved,
     * kaya hindi na magkakaiba ang lumalabas sa dalawang tab.
     */
    private function attachExpct($paginated)
    {
        $ppfNos = $paginated->getCollection()
            ->pluck('ppfno')
            ->map(fn($ppf) => (int) $ppf);

        $checkHF = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->unique(fn($r) => (int) $r->流動NO)   
            ->keyBy(fn($r) => (int) $r->流動NO);   

        $paginated->getCollection()->transform(function ($item) use ($checkHF) {
            $hf = $checkHF->get((int) $item->ppfno);
            $item->expct = $hf ? round($hf->未仕上) : 0;
            return $item;
        });

        return $paginated;
    }
}