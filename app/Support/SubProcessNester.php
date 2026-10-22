<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class SubProcessNester
{
    public static function nest(Collection $rows, array $parentBySubHfid, string $idKey = 'operatorid'): Collection
    {
        $norm = fn($v) => trim((string) $v);

        $rows = $rows->map(function (array $row) use ($parentBySubHfid, $idKey, $norm) {
            if (! empty($row['isSub'])) {
                $row['parentHfid'] = $parentBySubHfid[$norm($row[$idKey])] ?? null;
            }

            return $row;
        })->values();

        $mains = $rows->reject(fn($r) => ! empty($r['isSub']))->values();
        $subs  = $rows->filter(fn($r) => ! empty($r['isSub']))->values();

        if ($subs->isEmpty()) {
            return $rows;
        }

        // Position of the last main row of each inspector
        $lastIndex = [];
        foreach ($mains as $i => $row) {
            $lastIndex[$norm($row[$idKey])] = $i;
        }

        $subsByParent = $subs->groupBy(fn($r) => $norm($r['parentHfid'] ?? ''));
        $ordered      = collect();
        $placed       = [];

        foreach ($mains as $i => $row) {
            $ordered->push($row);

            $key = $norm($row[$idKey]);

            if (($lastIndex[$key] ?? null) === $i && $subsByParent->has($key)) {
                foreach ($subsByParent[$key] as $sub) {
                    $ordered->push($sub);
                }
                $placed[$key] = true;
            }
        }

        // Subs whose parent has no row in this table go last
        foreach ($subsByParent as $key => $group) {
            if (! isset($placed[$key])) {
                foreach ($group as $sub) {
                    $ordered->push($sub);
                }
            }
        }

        return $ordered->values();
    }
}