<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PcMaster;
use App\Repositories\Contracts\PcMasterRepositoryInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Business rules for tablet self-registration against the legacy
 * PCMaster table. Knows nothing about Livewire, HTTP, or the browser —
 * it's handed a UUID (and, when registering, a host name) and returns
 * or throws plain PHP types.
 */
class DeviceRegistrationService
{
    public function __construct(
        private readonly PcMasterRepositoryInterface $pcMasters,
    ) {
    }

    /**
     * Whether this tablet's UUID is already known in PCMaster.
     *
     * @throws InvalidArgumentException when the UUID is missing or malformed.
     */
    public function isKnownDevice(?string $uuid): bool
    {
        $this->assertValidUuid($uuid);

        return $this->pcMasters->findByUuid($uuid) !== null;
    }

    /**
     * Register a brand-new tablet: the operator has just typed a host
     * name into the popup, and we write the first PCMaster row for it.
     *
     * PlantDesgination is always left null here — nothing in this flow
     * ever sets it.
     *
     * @throws InvalidArgumentException when the UUID or host name is invalid.
     */
    public function registerNewHost(?string $uuid, ?string $hostName): PcMaster
    {
        $this->assertValidUuid($uuid);

        $hostName = filled($hostName) ? trim($hostName) : null;

        if (blank($hostName)) {
            throw new InvalidArgumentException('Host name is required.');
        }

        // Guard against a race where the same tablet double-submits
        // (e.g. a slow connection + an impatient tap) — never create
        // a duplicate row for the same UUID.
        $existing = $this->pcMasters->findByUuid($uuid);

        if ($existing !== null) {
            return $existing;
        }

        $pc = $this->pcMasters->create([
            'HostName' => $hostName,
            'UserName' => $uuid,
            'PlantDesgination' => null,
            'Status' => 1
        ]);

        logger()->info('Tablet registered in PCMaster.', [
            'uuid' => $uuid,
            'host_name' => $hostName,
        ]);

        return $pc;
    }

    private function assertValidUuid(?string $uuid): void
    {
        if (blank($uuid)) {
            throw new InvalidArgumentException('Device UUID is missing.');
        }

        if (! Str::isUuid($uuid)) {
            throw new InvalidArgumentException("Device UUID [{$uuid}] is not a valid UUID.");
        }
    }
}