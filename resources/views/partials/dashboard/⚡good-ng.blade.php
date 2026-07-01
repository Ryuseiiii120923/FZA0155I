<?php

use App\Services\Dashboard\GoodNgService;
use App\Services\Helper\DashboardDraftService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $ppf = 0;
    public int $excssqty = 0;
    public int $lackqty = 0;
    public int $reworkqty = 0;
    public int $sampleqty = 0;
    public int $goodqty = 0;
    public float $ngratioqty = 0;
    public bool $readonly = false;

    // Holds the base quantities from DB so we don't re-fetch on every compute
    protected array $base = [];

    #[On('ppf-checked')]
    public function getPPF(int $ppf = 0): void
    {
        $this->ppf  = $ppf;
        $this->base = app(GoodNgService::class)->getBaseQty($ppf);

        // Populate the editable fields from DB values
        $this->lackqty   = $this->base['lackqty'];
        $this->reworkqty = $this->base['reworkqty'];
        $this->sampleqty = $this->base['sampleqty'];

        $this->GoodNg();
    }

    #[On('progress-accepted')]
    public function setProgressQty(int $excssqty, int $lackqty, int $ppf): void
    {
        $this->ppf      = $ppf;
        $this->excssqty = $excssqty;
        $this->lackqty  = $lackqty;
        $this->GoodNg();
    }

    public function GoodNg(): void
    {
        if (empty($this->base)) {
            $this->base = app(GoodNgService::class)->getBaseQty($this->ppf);
        }

        $result = app(GoodNgService::class)->computeGoodNg(
            base:      $this->base,
            excssqty:  $this->excssqty,
            lackqty:   $this->lackqty,
            reworkqty: $this->reworkqty,
            sampleqty: $this->sampleqty,
        );

        $this->goodqty    = $result['goodqty'];
        $this->ngratioqty = $result['ngratioqty'];
        $this->syncDraft();
    }

    protected function syncDraft(): void
    {
        app(DashboardDraftService::class)->put($this->ppf, 'goodNg', [
            'excssqty'   => $this->excssqty,
            'lackqty'    => $this->lackqty,
            'reworkqty'  => $this->reworkqty,
            'sampleqty'  => $this->sampleqty,
            'goodqty'    => $this->goodqty,
            'ngratioqty' => $this->ngratioqty,
        ]);
    }

    #[On('read-only')]
    public function readOnly(bool $readonly = false): void
    {
        $this->readonly = $readonly;
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->ppf       = 0;
        $this->base      = [];
        $this->excssqty  = $this->lackqty = $this->reworkqty = 0;
        $this->sampleqty = $this->goodqty = 0;
        $this->ngratioqty = 0;
    }
};
?>

<div class="flex flex-col sm:flex-row  justify-center gap-4 mt-4 items-center @if($readonly) opacity-50 cursor-not-allowed @endif">
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="excss" class="block text-sm font-medium text-gray-700">Excess Qty</label>
        <input type="number" id="excss" inputmode="numeric"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" readonly required wire:blur="GoodNg" disabled wire:model.lazy="excssqty">
    </div>
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="lack" class="block text-sm font-medium text-gray-700">Lacking Qty</label>
        <input type="number" id="lack" inputmode="numeric"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" readonly required wire:blur="GoodNg" wire:model.lazy="lackqty" disabled min="0">
    </div>


    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="rework" class="block text-sm font-medium text-gray-700">Rework Qty</label>
        <input type="number" id="rework" inputmode="numeric"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" required wire:blur="GoodNg" wire:model.lazy="reworkqty">
    </div>
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="sample" class="block text-sm font-medium text-gray-700">Sample Qty</label>
        <input type="number" id="sample" inputmode="numeric"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" wire:blur="GoodNg" required wire:model.lazy="sampleqty">
    </div>
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="good" class="block text-sm font-medium text-gray-700">Good Qty</label>
        <input type="number" id="good" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" required wire:model.live="goodqty" readonly>
    </div>
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
        <label for="ng" class="block text-sm font-medium text-gray-700">NG Ratio</label>
        <input type="number" id="ng" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
            placeholder=" " value="" required wire:model.live="ngratioqty" readonly>
    </div>
    <button hidden wire:click="GoodNg" id="GoodNg"></button>
</div>