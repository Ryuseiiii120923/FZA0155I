<?php

use App\Services\Helper\DashboardDraftService;
use App\Services\Helper\DashboardSaveService;
use App\Traits\HasNotifications;
use App\Traits\WithLoading;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use WithLoading;
    use HasNotifications;

    public int $ppf = 0;
    public string $deviceUuid = '';
    #[Locked]
    public string $action = 'add';

    #[On('ppf-checked')]
    public function fetchPPF(int $ppf)
    {
        $this->ppf = $ppf;
    }
    #[On('action-changed')]
    public function onActionChanged(string $action): void
    {
        $this->action = $action;
    }

    public function submit()
    {
        if ($this->action === 'view') return;
        if ($this->action === 'delete') {
            app(DashboardSaveService::class)->delete($this->ppf);
            $this->notifyReload('success', 'The PPF has been deleted successfully.');
        } else {
            $draft = app(DashboardDraftService::class)->get($this->ppf);
            // validate completenessefore touching the DB
            foreach (['otherDetails', 'goodNg', 'inspectors', 'rework', 'defects'] as $section) {
                if (!isset($draft[$section])) {
                    $this->notifyFail('Incomplete', "Missing $section data.");
                    return;
                }
            }

            try {
                app(DashboardSaveService::class)->save($this->ppf, $draft, $this->deviceUuid);
                app(DashboardDraftService::class)->clear($this->ppf);
                $this->notifyReload('success', 'Inspection record saved.');
            } catch (\InvalidArgumentException $e) {
                // Validation errors from the service itself (missing hfGroup/hfDate)
                $this->notifyFail('Validation error', $e->getMessage());
            } catch (\Throwable $e) {
                // Unexpected errors (already logged inside DashboardSaveService)
                $this->notifyFail('Save failed', 'Something went wrong while saving. Please try again.');
            }
        }
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->ppf = 0;
    }
};
?>

<div x-data="{ init() { this.$wire.set('deviceUuid', localStorage.getItem('device_uuid')) } }" x-init="init()">
    @if($action !== '' && $action !== 'view')
    <div class="flex justify-center p-6">
        <button
            type="button"
            wire:click="submit"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-70 cursor-wait"
            wire:target="submit"
            @if($action==='delete' ) wire:confirm="Are you sure you want to delete this ppf?" @endif
            @class([ 'inline-flex items-center justify-center gap-2 px-12 py-2.5 rounded-lg text-white text-sm font-medium transition' , 'bg-blue-700 hover:bg-blue-800'=> $action === 'add',
            'bg-green-700 hover:bg-green-800' => $action === 'edit',
            'bg-red-700 hover:bg-red-800' => $action === 'delete',
            ])>

            {{-- Spinner: lalabas lang habang nagsu-submit --}}
            <svg wire:loading wire:target="submit" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>

            {{-- Normal label --}}
            <span wire:loading.remove wire:target="submit">
                {{ match($action) {
                    'add'    => 'Submit',
                    'edit'   => 'Update',
                    'delete' => 'Confirm Delete',
                    default  => 'Submit'
                } }}
            </span>

            {{-- Label habang naglo-load --}}
            <span wire:loading wire:target="submit">
                {{ match($action) {
                    'add'    => 'Saving...',
                    'edit'   => 'Updating...',
                    'delete' => 'Deleting...',
                    default  => 'Submitting...'
                } }}
            </span>
        </button>
    </div>
    @endif
</div>