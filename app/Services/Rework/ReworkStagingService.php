<?php

namespace App\Services\Rework;

/**
 * ReworkStagingService
 *
 * Pure, stateless functions for the rework staging workflow.
 * Every method receives arrays and returns new ones — no side effects,
 * easy to unit-test without booting Livewire.
 */
class ReworkStagingService
{
    // ----------------------------------------------------------
    // Staging CRUD
    // ----------------------------------------------------------

    public function addEntry(array $staged, array $entry): array
    {
        $staged[] = [
            'hfno'      => $entry['hfno'],
            'totalinsp' => $entry['totalinsp'],
            'type'      => trim($entry['type']),
            'qty'      => (int) $entry['qty'],
        ];

        return $staged;
    }

    public function removeEntry(array $staged, int $index): array
    {
        array_splice($staged, $index, 1);
        return $staged;
    }

    public function reset(): array
    {
        return [];
    }

    // ----------------------------------------------------------
    // Commit staged → committed
    // ----------------------------------------------------------

    /**
     * Merge all staged entries into the committed list.
     * Existing rows (same hfno + type) are updated in-place.
     */
    public function commitAll(array $committed, array $staged): array
    {
        foreach ($staged as $entry) {
            $idx = $this->findIndex($committed, $entry['hfno'], $entry['type']);

            if ($idx !== null) {
                $committed[$idx] = $entry;
            } else {
                $committed[] = $entry;
            }
        }

        return $committed;
    }

    /**
     * Update a single committed row (used by inline-edit).
     */
    public function updateCommitted(
        array  $committed,
        string $originalType,
        string $originalHfno,
        int    $newQuan,
        string $newHfno,
        int    $newTotalInsp
    ): array {
        foreach ($committed as &$row) {
            if ($row['type'] === $originalType && $row['hfno'] === $originalHfno) {
                $row['qty']      = $newQuan;
                $row['hfno']      = $newHfno;
                $row['totalinsp'] = $newTotalInsp;
                break;
            }
        }
        unset($row);

        return $committed;
    }

    // ----------------------------------------------------------
    // Utility
    // ----------------------------------------------------------

    /**
     * Pre-populate staging from committed rows (re-open modal to review).
     */
    public function loadFromCommitted(array $committed): array
    {
        return array_map(fn($r) => [
            'hfno'      => $r['hfno']      ?? '',
            'totalinsp' => $r['totalinsp'] ?? '',
            'type'      => $r['type']      ?? '',
            'qty'      => (int) ($r['qty'] ?? 0),
        ], $committed);
    }

    /**
     * Sum the 'qty' field across a list of rework rows.
     */
    public function sumNg(array $reworks): int
    {
        return (int) collect($reworks)->sum(fn($r) => (int) ($r['qty'] ?? 0));
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function findIndex(array $committed, string $hfno, string $type): ?int
    {
        $normalType = strtolower(trim($type));

        foreach ($committed as $i => $row) {
            if (
                trim($row['hfno'] ?? '') === $hfno &&
                strtolower(trim($row['type'] ?? '')) === $normalType
            ) {
                return $i;
            }
        }

        return null;
    }
}