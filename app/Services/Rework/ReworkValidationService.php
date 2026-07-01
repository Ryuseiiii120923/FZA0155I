<?php

namespace App\Services\Rework;

use App\Models\Auth\Worker as AuthWorker;
use App\Models\Auth\WorkerName as AuthWorkerName;
use App\Models\Rework\ReworkMaster;
use Illuminate\Support\Facades\Log;

class ReworkValidationService
{

    /**
     * Check that the given defect type exists in the master list.
     *
     * @param  string  $type  Raw value from the form field
     * @return array          Result
     */
    public function typeExistsInMaster(string $type): array
    {
        $normalised = strtolower(trim($type));

        $exists = ReworkMaster::query()
            ->select('DefectType')
            ->distinct()
            ->whereNotNull('DefectType')
            ->get()
            ->pluck('DefectType')
            ->map(fn($d) => strtolower(trim($d)))
            ->contains($normalised);

        if (!$exists) {
            return $this->fail('newRework', 'This rework defect does not exist in the master list.');
        }

        return $this->pass();
    }

    /**
     * Ensure the type has not already been staged in this session.
     *
     * @param  array   $staged      Current stagedReworks array
     * @param  string  $hfno        Current HF number
     * @param  string  $type        Defect type (raw)
     * @return array                Result
     */
    public function notAlreadyStaged(array $staged, string $hfno, string $type): array
    {
        $normalised = strtolower(trim($type));

        $duplicate = collect($staged)->contains(
            fn($r) => ($r['hfno'] ?? '') === $hfno
                   && strtolower(trim($r['type'] ?? '')) === $normalised
        );

        if ($duplicate) {
            return $this->fail('newRework', 'This rework type is already staged for this session.');
        }

        return $this->pass();
    }

    /**
     * Ensure the type has not already been committed for this HF number.
     *
     * @param  array   $committed   Current reworkss array
     * @param  string  $hfno        Current HF number
     * @param  string  $type        Defect type (raw)
     * @return array                Result
     */
    public function notAlreadyCommitted(array $committed, string $hfno, string $type): array
    {
        $normalised = strtolower(trim($type));

        $duplicate = collect($committed)->contains(
            fn($r) => ($r['hfno'] ?? '') === $hfno
                   && strtolower(trim($r['type'] ?? '')) === $normalised
        );

        if ($duplicate) {
            return $this->fail('newRework', 'This rework type has already been added for this operator.');
        }

        return $this->pass();
    }

    /**
     * Enforce the 5-unique-HF-number limit across committed + staged lists.
     *
     * @param  array   $committed   Current reworkss array
     * @param  array   $staged      Current stagedReworks array
     * @param  string  $hfno        HF number being added now
     * @return array                Result
     */
    public function withinHfLimit(array $committed, array $staged, string $hfno, int $limit = 5): array
    {
        $committedHfs = collect($committed)->pluck('hfno');
        $stagedHfs    = collect($staged)->pluck('hfno');
        $allHfs       = $committedHfs->merge($stagedHfs)->unique();

        $isNew = !$allHfs->contains($hfno);

        if ($isNew && $allHfs->count() >= $limit) {
            return $this->fail('hfno', "You can only add up to {$limit} HF Numbers.");
        }

        return $this->pass();
    }

    // -------------------------------------------------------------------------
    // HF (operator) validation
    // -------------------------------------------------------------------------

    /**
     * Look up an operator by their HF code and return their display name.
     *
     * Returns:
     *   ok=true  + 'name' key on success
     *   ok=false + 'field'/'message' on failure
     *
     * @param  string  $hfno  Raw HF code from the form
     * @return array          Result (may include 'name' key on success)
     */
    public function resolveOperator(string $hfno): array
    {
        try {
            if (empty(trim($hfno))) {
                return $this->pass(['name' => null]);
            }

            // Two-character codes are stored with a leading space in the DB
            $searchValue = (strlen($hfno) === 2) ? " {$hfno}" : $hfno;

            $worker = AuthWorker::where('作業員CD', $searchValue)->first();

            if (!$worker) {
                return $this->fail('hfno', 'This Operator does not exist.');
            }

            $workerName = AuthWorkerName::where('社員CD', $worker->社員CD)->first();

            return $this->pass(['name' => $workerName?->名前]);

        } catch (\Exception $e) {
            Log::error('ReworkValidationService::resolveOperator — ' . $e->getMessage(), [
                'hfno' => $hfno,
            ]);

            return $this->fail('hfno', 'An error occurred while validating the operator code.');
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function pass(array $extra = []): array
    {
        return array_merge(['ok' => true, 'field' => null, 'message' => null], $extra);
    }

    private function fail(string $field, string $message): array
    {
        return ['ok' => false, 'field' => $field, 'message' => $message];
    }
}