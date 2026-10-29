<?php

namespace App\Repositories\Dashboard;

use App\Models\AddDefect;
use App\Models\Inspector\MasterData;
use App\Models\PPFHeader\CheckHF;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PPFConfirmRepository
{
    private const VERSION_KEY = 'ppf_confirm:version';
    private const TTL_SECONDS = 60;
    private const MAX_ROWS = 500;

    public function fetchData(?string $search = null, int $perPage = 5): LengthAwarePaginator
    {
        return $this->paginate('pending', $search, $perPage);
    }

    public function fetchSavedData(?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginate('saved', $search, $perPage);
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, (string) Str::uuid());
    }

    private function paginate(string $type, ?string $search, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $rows = collect($this->cachedRows($type, $search));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values()->map(fn($row) => (object) $row),
            $rows->count(),
            $perPage,
            $page,
            [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * Isang query (TOP 500) kada search term. Naka-cache bilang array,
     * hindi depende sa page o perPage.
     */
    private function cachedRows(string $type, ?string $search): array
    {
        return Cache::remember(
            $this->cacheKey($type, $search),
            self::TTL_SECONDS,
            function () use ($type, $search) {
                $rows = $type === 'pending'
                    ? $this->queryPending($search)
                    : $this->querySaved($search);

                return $this->attachExpct($rows)
                    ->map(fn($item) => [
                        'ppfno'         => $item->ppfno,
                        'total_inspect' => $item->total_inspect,
                        'updated_at'    => (string) $item->updated_at,
                        'expct'         => $item->expct,
                    ])
                    ->all();
            }
        );
    }

    private function cacheKey(string $type, ?string $search): string
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn() => (string) Str::uuid());

        return "ppf_confirm:{$version}:{$type}:" . md5(trim((string) $search));
    }

    private function queryPending(?string $search): Collection
    {
        return MasterData::query()
            ->from('inspector_pr_hf_summary')
            ->select('ppfno', 'total_inspect', 'updated_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('FF.dbo.HFDefectSummary')
                    ->whereColumn('FF.dbo.HFDefectSummary.PPFNoStr', 'PRecord.dbo.inspector_pr_hf_summary.ppfno');
            })
            ->when($search, fn($q) => $q->where('ppfno', 'like', "%{$search}%"))
            ->orderByDesc('updated_at')
            ->orderByDesc('ppfno')
            ->limit(self::MAX_ROWS)
            ->get();
    }

    private function querySaved(?string $search): Collection
    {
        return DB::table('FF.dbo.HFDefectSummary')
            ->selectRaw('PPFNo as ppfno')
            ->selectRaw('Total as total_inspect')
            ->selectRaw('DateEncode as updated_at')
            ->when($search, function ($q) use ($search) {
                $q->whereRaw(
                    'CAST(CAST(PPFNo AS BIGINT) AS NVARCHAR(50)) LIKE ?',
                    ["%{$search}%"]
                );
            })
            ->orderByDesc('DateEncode')
            ->limit(self::MAX_ROWS)
            ->get();
    }

    /**
     * Pareho ang lookup ng expct para sa pending at saved.
     */
    private function attachExpct(Collection $rows): Collection
    {
        $ppfNos = $rows->pluck('ppfno')->map(fn($ppf) => (int) $ppf)->unique()->values();

        $checkHF = CheckHF::whereIn('流動NO', $ppfNos)
            ->get()
            ->unique(fn($r) => (int) $r->流動NO)
            ->keyBy(fn($r) => (int) $r->流動NO);

        return $rows->each(function ($item) use ($checkHF) {
            $hf = $checkHF->get((int) $item->ppfno);
            $item->expct = $hf ? round($hf->未仕上) : 0;
        });
    }
}
