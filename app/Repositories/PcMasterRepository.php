<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PcMaster;
use App\Repositories\Contracts\PcMasterRepositoryInterface;

/**
 * All raw database access for PCMaster lives here and nowhere else.
 * DeviceRegistrationService never talks to Eloquent directly.
 */
class PcMasterRepository implements PcMasterRepositoryInterface
{
    public function findByUuid(string $uuid): ?PcMaster
    {
        return PcMaster::query()
            ->where('UserName', $uuid)
            ->first();
    }

    public function create(array $attributes): PcMaster
    {
        return PcMaster::query()->create($attributes);
    }
}