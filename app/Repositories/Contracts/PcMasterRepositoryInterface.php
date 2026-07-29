<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\PcMaster;

interface PcMasterRepositoryInterface
{
    /**
     * Look up a tablet by the UUID stored in the UserName column.
     */
    public function findByUuid(string $uuid): ?PcMaster;

    /**
     * Insert a new PCMaster row for a tablet that has never connected before.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PcMaster;
}