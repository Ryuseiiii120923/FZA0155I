<?php

use App\Action\ProcessRecord\DeletePpfAction;
use App\Services\ProcessRecord\ProcessRecordService;
use App\Traits\HasNotifications;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;
    use HasNotifications;
    public bool $loading = false;
    public int $userEncoder = 0;
    public string $search = '';


    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function editPPF(int $ppf)
    {
        $this->dispatch('confirm-ppf', ppf: $ppf, encoder: $this->userEncoder, readonly: false);
        $this->dispatch('read-only', readonly: false);
    }

    public function deletePPF($ppf)
    {
        try {
            app(DeletePpfAction::class)->execute($ppf, $this->userEncoder);
            $this->notifyReload('success', 'The Data has been delete.');
        } catch (\Throwable $e) {
            Log::error('Error deleting data', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'There are error when deleting the Data');
        }
    }

    public function viewPPF($ppf)
    {
        $this->dispatch('confirm-ppf', ppf: $ppf, encoder: $this->userEncoder, readonly: true);
        $this->dispatch('read-only', readonly: true);
    }

    public function mount()
    {
        $this->userEncoder = Auth::user()->社員CD;
    }

    #[Computed]
    public function ppfRecord()
    {
        return app(ProcessRecordService::class)->fetchProcessRecordData($this->userEncoder,$this->search );
    }
};
?>

<div>

    <div class="flex justify-center mt-8">
        @if (session()->has('failed'))
        <div
            x-data="{ open: true }"
            x-show="open"
            class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <!-- Close Button -->
                <button
                    @click="open = false"
                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    ✕
                </button>

                <!-- Modal Content -->
                <h2 class="text-lg font-semibold text-red-600 mb-2">Failed</h2>
                <p class="text-gray-700 mb-4">{{ session('failed') }}</p>

                <button
                    @click="open = false;
            location.reload();
            "
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    OK
                </button>
            </div>
        </div>
        @endif

        <div class="overflow-x-auto rounded-lg  w-9/12">
            <div class="w-full flex justify-end mb-3">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search PPF No..."
                    class="border border-gray-400 rounded px-3 py-2 text-black w-full sm:w-64">
            </div>
            <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-900 text-left">
                    <tr>
                        <th class="px-6 py-5">PPFNo</th>
                        <th class="px-6 py-5">Date</th>
                        <th class="px-6 py-5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->ppfRecord as $record)
                    <tr class="border-b border-gray-700">
                        <td class="px-4 py-2">{{ (int) $record->ppfno }}</td>
                        <td class="px-4 py-2">{{ $record->created_at }}</td>
                        <td class="px-4 py-2 flex gap-2">
                            <button wire:click="editPPF('{{ $record->ppfno }}')" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">
                                Edit
                            </button>
                            <button
                                @click.prevent="if (confirm('Are you sure you want to delete this record?')) $wire.deletePPF(@js($record->ppfno))"
                                class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded">
                                Delete
                            </button>
                            <button wire:click="viewPPF('{{ $record->ppfno }}')" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                                View
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-3 py-5 text-center">No records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
    @if ($loading)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            ⏳ Processing...
        </div>
    </div>
    @endif
</div>