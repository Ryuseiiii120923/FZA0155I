<?php

use App\Traits\HasNotifications;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use HasNotifications;

    public string $action = ''; // 'add', 'edit', 'view'
    public int $selectedPpf = 0;

    public function setAction(string $action): void
    {
        $this->action = $action;
        $this->dispatch('action-changed', action: $action);
        $this->dispatch('read-only', false);
    }
};
?>

<div>
    <livewire:notification.pop-up-notif />

    @php
    $actions = [
    'add' => ['ti-plus', 'Add', 'border-blue-600 bg-blue-50 text-blue-700'],
    'edit' => ['ti-edit', 'Update', 'border-green-700 bg-green-50 text-green-700'],
    'view' => ['ti-eye', 'View', 'border-yellow-500 bg-yellow-50 text-yellow-700'],
    'delete' => ['ti-trash', 'Delete', 'border-red-700 bg-red-50 text-red-700'],
    ];
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 p-3 sm:p-6">
        @foreach($actions as $key => [$icon, $label, $activeClasses])
        <button
            wire:click="setAction('{{ $key }}')"
            @class([ 'flex flex-col items-center gap-1.5 py-3 w-full rounded-xl border-2 text-sm font-medium transition-all' , 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'=> $action !== $key,
            $activeClasses => $action === $key,
            ])>
            <i class="ti {{ $icon }} text-xl"></i>
            <span>{{ $label }}</span>
        </button>
        @endforeach
    </div>

    @stack('styles')
    <livewire:partials::dashboard.ppf-confirm />
    <livewire:partials::dashboard.total-inspection />
    <livewire:partials::dashboard.checkppf />

    <div class="flex flex-col gap-6 mt-4 w-full px-2 sm:px-4">
        <div class="w-full min-w-0">
            <livewire:partials::dashboard.defectsTable />
        </div>
        <div class="w-full min-w-0">
            <livewire:partials::dashboard.rework-table />
        </div>
    </div>

    <livewire:partials::dashboard.good-ng />
    <livewire:partials::dashboard.other-details />
    <livewire:partials::dashboard.add />
</div>