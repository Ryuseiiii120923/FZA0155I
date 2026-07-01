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
    <div class="flex justify-center gap-3 p-6">
        @foreach([
        'add' => ['ti-plus', 'Add', 'blue'],
        'edit' => ['ti-edit', 'Update', 'green'],
        'view' => ['ti-eye', 'View', 'yellow'],
        'delete' => ['ti-trash', 'Delete', 'red'],
        ] as $key => [$icon, $label, $color])
        <button
            wire:click="setAction('{{ $key }}')"
            @class([ 'flex flex-col items-center gap-1.5 py-3 flex-1 rounded-xl border-2 text-sm font-medium transition-all' ,
             'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'=> $action !== $key,
            'border-blue-600 bg-blue-50 text-blue-700' => $action === $key && $key === 'add',
            'border-green-700 bg-green-50 text-green-700' => $action === $key && $key === 'edit',
            'border-yellow-500 bg-yellow-50 text-yellow-700' => $action === $key && $key === 'view',
            'border-red-700 bg-red-50 text-red-700' => $action === $key && $key === 'delete',
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
    <div class="flex flex-col sm:flex-row gap-6 mt-4 items-start justify-center w-full">
        <div class="w-11/12 sm:w-1/2 flex justify-center">

            <livewire:partials::dashboard.defectsTable />
        </div>
        <div class="w-11/12 sm:w-1/2 flex justify-center">

            <livewire:partials::dashboard.rework-table />
        </div>
    </div>

    <livewire:partials::dashboard.good-ng />
    <livewire:partials::dashboard.other-details />
    <livewire:partials::dashboard.add/>

</div>