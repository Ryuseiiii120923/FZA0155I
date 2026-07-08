<?php

use App\Services\Dashboard\PPFConfirmService;
use App\Traits\HasNotifications;
use App\Traits\WithLoading;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;
    use WithLoading;
    use HasNotifications;
    public string $search = '';
    public bool $isHideTable = true;

    #[Locked]
    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('action-changed')]
    public function onActionChanged(string $action): void
    {
        $this->action = $action;
        $this->isHideTable = empty($action);  // ← moved out of computed
        $this->resetPage();
        $this->dispatch('reset-form');
        $this->search = '';
    }

    #[Computed]
    public function ppfdata()
    {
        if (empty($this->action)) {
            return null;
        }

        if ($this->action === 'edit' || $this->action === 'view' || $this->action === 'delete') {
            return app(PPFConfirmService::class)->fetchSavedData($this->search);
        } else {
            return app(PPFConfirmService::class)->fetchData($this->search);
        }
    }

    public function confirm_ppf(int $ppf): void
    {
        if ($ppf === 0 && $this->ppf === 0) {
            $this->notifyFail('Error', 'No PPF selected.');
            $this->dispatch('hide-loading');
            return;
        }

        if ($this->action === 'delete') {
            $this->dispatch('confirm-ppf', ppf: $ppf, readonly: true, action: $this->action);
             $this->dispatch('read-only', readonly: true);
            return;
        }
        $encoder = Auth::user()->社員CD;
        $readonly = $this->action === 'view';
        if ($this->action === 'view') {
            $this->dispatch('read-only', readonly: true);
        }
        $this->dispatch('confirm-ppf', ppf: $ppf, encoder: $encoder, readonly: $readonly, action: $this->action);
    }
};
?>


<div class="w-full flex flex-col items-center mt-3 gap-3">
    @unless($isHideTable)
    <div class="w-full flex justify-end">
        <input
            type="text"
            wire:model.live.debounce.400ms="search"
            placeholder="Search PPF No..."
            class="border border-gray-400 rounded px-3 py-2 text-black w-full sm:w-64">
    </div>

    <div class="w-full overflow-x-auto">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-center">
                <tr>
                    <th class="px-4 py-2">PPFNo</th>
                    <th class="px-6 py-2">Inspection Total</th>
                    <th class="px-4 py-2">Encode Date</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($this->ppfdata as $data)
                
                <tr>
                    <td class="px-4 py-2 text-center">{{ (int)$data->ppfno }}</td>
                    <td class="px-4 py-2 text-center">{{ (int)$data->total_inspect }} / {{ $data->expct }}</td>
                    <td class="px-4 py-2 text-center">{{ $data->updated_at }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        @if($action === 'add')
                        <button class="text-white bg-green-700 px-4 py-2 rounded"
                            wire:loading.attr="disabled"
                            wire:click.throttle.10000ms="confirm_ppf({{ $data->ppfno }})">
                            Confirm
                        </button>

                        @elseif($action === 'edit')
                        <button class="text-white bg-blue-600 px-4 py-2 rounded"
                            wire:loading.attr="disabled"
                            wire:click.throttle.10000ms="confirm_ppf({{ $data->ppfno }})"
                            @click="window.dispatchEvent(new CustomEvent('show-loading', { detail: { message: 'Loading PPF...' } }))">
                            Edit
                        </button>

                        @elseif($action === 'view')
                        <button class="text-white bg-gray-500 px-4 py-2 rounded"
                            wire:loading.attr="disabled"
                            wire:click.throttle.10000ms="confirm_ppf({{ $data->ppfno }})"
                            @click="window.dispatchEvent(new CustomEvent('show-loading', { detail: { message: 'Loading PPF...' } }))">
                            View
                        </button>

                        @elseif($action === 'delete')
                        <button class="text-white bg-red-600 px-4 py-2 rounded"
                            wire:loading.attr="disabled"
                            wire:click.throttle.10000ms="confirm_ppf({{ $data->ppfno }})"
                            @click="window.dispatchEvent(new CustomEvent('show-loading', { detail: { message: 'Deleting PPF...' } }))">
                            Delete
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">No defects added yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="w-full">
        {{ $this->ppfdata->links() }}
    </div>
    @endunless
</div>