<?php

use App\Services\Dashboard\OtherDetailsService;
use App\Services\Helper\DashboardDraftService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public array $fnMachVal = [];
    public string $finishingMachine = '';
    public string $plant = '';
    public string $hfDate = '';
    public string $details = '';
    public string $hfGroup = '';
    public string $updateDate = '';
    public mixed $encoder;
    public int $ppf = 0;
    public bool $readonly = false;
    public array $autoMachines = [];
    public string $name = '';

    #[On('ppf-checked')]
    public function fetchData(int $ppf, int $encoder)
    {
        $this->ppf = $ppf;
        $this->encoder = $encoder;
        $this->fnMachVal = app(OtherDetailsService::class)->getAllFNMachine()->toArray();
        $result = app(OtherDetailsService::class)->getOtherDetails($ppf, $encoder);
        $this->fill($result);
        $this->syncDraft();
    }

    public function updated($property)
    {
        $this->syncDraft();
    }

    protected function syncDraft(): void
    {
        app(DashboardDraftService::class)->put($this->ppf, 'otherDetails', [
            'finishingMachine' => $this->autoMachines,
            'plant'            => $this->plant,
            'hfDate'           => $this->hfDate,
            'details'          => $this->details,
            'hfGroup'          => $this->hfGroup,
            'encoder'          => $this->encoder,
        ]);
    }
    #[On('read-only')]
    public function readOnly(bool $readonly = false)
    {
        $this->readonly = $readonly;
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->finishingMachine = $this->plant = $this->hfDate = '';
        $this->details = $this->hfGroup = $this->updateDate =  '';
        $this->encoder = 0;
    }

    public function updatedFinishingMachine($value): void
    {
        if ($value && !in_array($value, $this->autoMachines)) {
            $this->autoMachines[] = $value;
        }
    }

    public function removeMachine(int $index): void
    {
        array_splice($this->autoMachines, $index, 1);
        $this->syncDraft();
    }
};
?>

<div class="@if($readonly) opacity-50 cursor-not-allowed @endif">
    <div class=" flex flex-row gap-2 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full mx-auto">
            <label for="automach" class="block text-sm font-medium text-gray-700">
                Finishing Machine
            </label>

            <select id="automach"
                class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                wire:model.live="finishingMachine"
                required>
                <option value="">-- Select Finishing Machine --</option>
                @foreach ($fnMachVal as $val )
                <option value="{{ $val }}">{{ $val }}</option>
                @endforeach
            </select>

            @if(count($autoMachines) > 0)
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($autoMachines as $index => $machine )
                <span wire:key="machine-{{ $index }}"
                    class="flex items-center gap-1 bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-xl">
                    {{ $machine }}

                    @unless ($readonly)
                    <button wire:click="removeMachine({{ $index }})"
                        class="ml-1 text-blue-500 hover:text-red-600 font-bold text-sm leading-none">
                        &times;

                    </button>
                    @endunless
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <div class=" flex flex-row gap-2 mt-4 items-center mx-6 sm:mx-2">
        @include('components.ui.datet-time-picker')
        <div class="w-full mx-auto">
            <label for="upd" class="block text-sm font-medium text-gray-700 text-center">Update Date</label>
            <input type="text" id="upd" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " wire:model="updateDate" required readonly>
        </div>


    </div>

    <div class=" flex flex-row gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
            <input type="text" id="details" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model.live="details">
        </div>
    </div>

    <div class=" flex flex-row gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="inspection_group" class="block text-sm font-medium text-gray-700">HF Group</label>
            <input type="text" id="inspection_group" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model.live="hfGroup">
        </div>
    </div>
    <div class=" flex flex-row gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="registrant" class="block text-sm font-medium text-gray-700">Registrant</label>
            <input type="text" id="registrant" class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="{{ $name }}" required readonly>
        </div>
    </div>

</div>