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

            app(DashboardSaveService::class)->save($this->ppf, $draft);
            app(DashboardDraftService::class)->clear($this->ppf);
            $this->notifyReload('success', 'Inspection record saved.');
        }
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->ppf = 0;
    }
};
?>

<div>
    @if($action !== '' && $action !== 'view')
    <div class="flex justify-center p-6">
        <button
            wire:click="submit"
            @if($action == 'delete') @click.prevent="if (confirm('Are you sure you want to delete this ppf?')) $wire.submit()" @endif
            @class([ 'px-12 py-2.5 rounded-lg text-white text-sm font-medium transition' , 'bg-blue-700 hover:bg-blue-800'=> $action === 'add',
            'bg-green-700 hover:bg-green-800' => $action === 'edit',
            'bg-red-700 hover:bg-red-800' => $action === 'delete',
            ])>
            {{ match($action) {
            'add'    => 'Submit',
            'edit'   => 'Update',
            'delete' => 'Confirm Delete',
            default  => 'Submit'
        } }}
        </button>
    </div>
    @endif
</div>