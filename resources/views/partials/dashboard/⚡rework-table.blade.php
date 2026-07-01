<?php

use App\Services\Dashboard\ReworkService;
use App\Services\Helper\DashboardDraftService;
use App\Traits\WithLoading;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use WithLoading;
    public int $ppf = 0;
    public array $rework = [];
    public int $totalNgRework = 0;
    public bool $readonly = false;
    #[On('ppf-checked')]
    public function fetchRework(int $ppf)
    {
        $this->ppf = $ppf;
        $data = app(ReworkService::class)->fetchRework($ppf);

        $this->rework = $data['reworks'];
        $this->totalNgRework = $data['total'];
        $this->syncDraft();
    }
      #[On('read-only')]
    public function readOnly(bool $readonly = false)
    {
        $this->readonly = $readonly;
    }

    protected function syncDraft(): void
    {
        app(DashboardDraftService::class)->put($this->ppf, 'rework', [
            'rework'        => $this->rework,
            'totalNgRework' => $this->totalNgRework,
        ]);
    }

    #[On('reset-form')]
public function resetForm(): void
{
    $this->rework = [];
    $this->totalNgRework = 0;
}
};
?>

<x-ui.wrapper.card-wrapper :readonly="$readonly">
    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Rework</p>
    </div>
    <div class="overflow-x-auto mt-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Inspector ID</th>
                    <th class="px-4 py-2">Inspector Name</th>
                    <th class="px-4 py-2">RWK Defect</th>
                    <th class="px-4 py-2">Qty</th>
                    <th class="px-4 py-2">Total Insp</th>
                    <th class="px-4 py-2">Date Encode</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $rework as $reworks )
                <tr wire:key="rework-{{ $reworks['type'] }}">
                    <td class="px-4 py-2">{{ $reworks['operatorid'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['operatorname'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['qty'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['dateEncode'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center">No rework added yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="TotalNgRework" class="block text-sm font-medium text-black">Total NG Rework</label>
            <input type="text" id="TotalNgRework" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " required wire:model="totalNgRework" readonly>
        </div>
    </div>
</x-ui.wrapper.card-wrapper>