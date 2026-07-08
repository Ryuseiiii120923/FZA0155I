<?php

use App\Services\Dashboard\PPFService;
use App\Services\Helper\DashboardDraftService;
use App\Traits\HasNotifications;
use App\Traits\WithLoading;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Locked;

new class extends Component
{
    use WithLoading;
    use HasNotifications;
    public int $ppf = 0;
    public string $partno = '';
    public string $lotno = '';
    public string $matno = '';
    public string $moldno = '';
    public string $shift = '';
    public string $opt = '';
    public int $expct = 0;
    public string $progressInsp = '';
    public bool $isShowAccept = true;
    public int $totalInspected = 0, $expected = 0;
    public bool $readonly = false;
    public string $action = ""; 
    #[Locked]
    public string $currentPage = 'dashboard';

    #[On('confirm-ppf')]
    public function checkPPF(int $ppf = 0, int $encoder = 0, bool $readonly = false, string $action = 'add')
    {
        $this->action = $action;
        $this->ppf = ($ppf != 0) ? $ppf : $this->ppf;
        $result = app(PPFService::class)->fetchMainData($this->ppf, $action);
        if (isset($result['error'])) {
            $this->notifyFail('Error', $result['error']);
            return;
        }
        $this->fill($result);
        $parts = explode('/', $result['progressInsp']);
        $this->totalInspected = (int)$parts[0];
        $this->expected = (int)$parts[1];
        $this->isShowAccept = ((int)$parts[0] === (int) $parts[1]) ? true : false;
        $this->syncDraft();
        $this->dispatch('ppf-checked', ppf: $this->ppf, encoder: $encoder);
        if ($encoder !== 0) {

            $this->dispatch('edit-ppf', ppf: $this->ppf, encoder: $encoder, readonly: $readonly);
        }
    }

    #[On('post-ppf')]
    public function checkPPFQR()
    {
        $ppf = $this->ppf; // fallback
        if (request()->has('ppf')) {
            $ppf = request()->input('ppf');
        }
        $this->ppf = (int) $ppf;
        $this->checkPPF($this->ppf);
    }

    public function acceptProgressQty()
    {
        $excess = max($this->totalInspected - $this->expected, 0);
        $lack   = max($this->expected - $this->totalInspected, 0);

        $this->dispatch('progress-accepted', excssqty: $excess, lackqty: $lack, ppf: $this->ppf);
    }

    protected function syncDraft(): void
    {
        app(DashboardDraftService::class)->put($this->ppf, 'header', [
            'partno' => $this->partno,
            'lotno'  => $this->lotno,
            'matno'  => $this->matno,
            'moldno' => $this->moldno,
            'shift'  => $this->shift,
            'opt'    => $this->opt,
            'expct'  => $this->expct,
            'totalInspected' => $this->totalInspected,
            'ppfno' => $this->ppf,
        ]);
    }

    public function mount(string $currentPage = 'dashboard'): void
    {
        $this->currentPage = $currentPage;
    }

    #[On('read-only')]
    public function readOnly(bool $readonly = false)
    {
        $this->readonly = $readonly;
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->ppf = 0;
        $this->partno = $this->lotno = $this->matno = '';
        $this->moldno = $this->shift = $this->opt = '';
        $this->expct = 0;
        $this->progressInsp = '';
        $this->totalInspected = $this->expected = 0;
        $this->isShowAccept = true;
    }
};
?>

<div class="bg-white shadow-lg px-3 py-4 @if($readonly) opacity-50 cursor-not-allowed @endif" x-data="{ loading: false }">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">

        <!-- PPF Input (smaller width) -->
        <div class="flex flex-col mx-5 sm:mx-2">
            <label for="PPF" class="block text-md font-medium text-black mb-1">PPF</label>
            <input
                type="number"
                id="PPF"
                class="w-40 border border-black w-full rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter PPF"
                wire:model="ppf"
                @blur="
        window.dispatchEvent(new CustomEvent('show-loading', { detail: { message: 'Checking PPF...' } }));
        $wire.set('ppf', parseInt($event.target.value) || 0).then(() => $wire.checkPPF())
    ">
        </div>

        <!-- Button -->
        @include('components.ui.scanppf-button')

    </div>

    <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
        <x-ui.dashboard.input-field
            id="PartNo"
            label="Part No"
            wire:model="partno"
            readonly />

        <x-ui.dashboard.input-field
            id="LotNo"
            label="Lot No"
            wire:model="lotno"
            readonly />


        <x-ui.dashboard.input-field
            id="MatNo"
            label="Mat No"
            wire:model="matno"
            readonly />

        <x-ui.dashboard.input-field
            id="MoldNo"
            label="Mold No"
            wire:model="moldno"
            readonly />

        <x-ui.dashboard.input-field
            id="shift"
            label="Shift"
            wire:model="shift"
            readonly />

        <x-ui.dashboard.input-field
            id="opt"
            label="Operator"
            wire:model="opt"
            readonly />

        <x-ui.dashboard.input-field
            id="expct"
            label="Expected Qty"
            wire:model="expct"
            readonly />
    </div>
    <div class="flex flex-col mt-3 w-11/12 sm:w-1/3 mx-5 sm:mx-2" @if($currentPage==='defect' ) hidden @endif>
        <!-- Label -->
        <label for="ProgressInsp" class="block text-sm font-medium text-black">Inspection Progress</label>
        <!-- Input + Button on same line -->
        <div class="flex items-center gap-2 mt-1"> <input type="text"
                id="ProgressInsp"
                class="flex-1 border border-black rounded-md px-2 py-1 me-4"
                placeholder=" "
                required
                wire:model="progressInsp"
                readonly>
            @unless($action != 'add')
            <button type="button"
                wire:click="acceptProgressQty" @if($isShowAccept) hidden @endif class="px-4 py-1 ms-3 bg-blue-600 text-white rounded-md
               hover:bg-blue-700 hover:shadow-md transition duration-200"
                wire:confirm="Are you sure you want to accept this?">
                Accept
            </button>
            @endunless
        </div>
    </div>
</div>