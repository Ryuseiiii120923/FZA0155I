<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DeviceRegistrationService;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

class DeviceRegistration extends Component
{
    public ?string $uuid = null;
    public bool $deviceKnown = false;
    public bool $showRegistrationPrompt = false;

    public string $hostNameInput = '';
    public function registerDevice(?string $uuid, DeviceRegistrationService $deviceService): void
    {
        $this->uuid = $uuid;

        try {
            $known = $deviceService->isKnownDevice($uuid);
        } catch (InvalidArgumentException $e) {
            logger()->warning('Device check skipped: invalid UUID.', [
                'uuid' => $uuid,
                'message' => $e->getMessage(),
            ]);
            $this->deviceKnown = true;
            $this->showRegistrationPrompt = false;

            return;
        } catch (Throwable $e) {
            logger()->error('Device check failed.', [
                'uuid' => $uuid,
                'message' => $e->getMessage(),
            ]);
            $this->deviceKnown = true;
            $this->showRegistrationPrompt = false;

            return;
        }

        $this->deviceKnown = $known;
        $this->showRegistrationPrompt = ! $known;
    }
    public function submitHostName(DeviceRegistrationService $deviceService): void
    {
        $this->validate([
            'hostNameInput' => ['required', 'string', 'max:255'],
        ]);

        try {
            $deviceService->registerNewHost($this->uuid, $this->hostNameInput);
        } catch (InvalidArgumentException $e) {
            $this->addError('hostNameInput', $e->getMessage());

            return;
        } catch (Throwable $e) {
            logger()->error('Device registration failed.', [
                'uuid' => $this->uuid,
                'message' => $e->getMessage(),
            ]);

            $this->addError('hostNameInput', 'Something went wrong saving this device. Please try again.');

            return;
        }

        $this->deviceKnown = true;
        $this->showRegistrationPrompt = false;
        $this->hostNameInput = '';
    }

    public function render(): View
    {
        return view('livewire.device-registration');
    }
}