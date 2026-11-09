<?php

use App\Services\Dashboard\DefectService;
use App\Services\Helper\DashboardDraftService;
use App\Traits\WithLoading;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    use WithLoading;
    public int $ppf;
    public array $defects = [];
    public array $smallDefects = [];
    public int $totalNG = 0;
    public bool $readonly = false;

    #[On('ppf-checked')]
    public function loadDefects(int $ppf)
    {
        $this->startLoading('Fetching Defects....', 'Fetching Defects. Please Wait');
        $this->ppf = $ppf;
        $data = app(DefectService::class)->fetchDefect($ppf);
        $this->defects = $data['defects'];
        $this->smallDefects = $data['smallDefects'];
        $this->totalNG = $data['totalQty'];
        $this->syncDraft();
        $this->stopLoading();
    }

    #[On('read-only')]
    public function readOnly(bool $readonly = false)
    {
        $this->readonly = $readonly;
    }

    #[On('reset-form')]
    public function resetForm(): void
    {
        $this->defects = [];
        $this->smallDefects = [];
        $this->totalNG = 0;
    }

    protected function syncDraft(): void
    {
        app(DashboardDraftService::class)->put($this->ppf, 'defects', [
            'defects'      => $this->defects,
            'smallDefects' => $this->smallDefects,
            'totalNG'      => $this->totalNG,
        ]);
    }
};
?>

<x-ui.wrapper.card-wrapper :readonly="$readonly">
    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold text-center text-white p-4">Defect</p>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Inspector ID</th>
                    <th class="px-4 py-2">Inspector Name</th>
                    <th class="px-6 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Endcode Date</th>
                    <th class="px-4 py-2">Process</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($defects as $defect)
                    @php
                        $isSub      = ! empty($defect['isSub']);
                        $operatorId = $defect['operatorid'] ?? '';
                        $process    = $defect['process'] ?? '';
                        $type       = $defect['type'] ?? '';
                        $qty        = $defect['qty'] ?? 0;
                        $children   = data_get($smallDefects, [$type, $process, $operatorId], []);
                    @endphp

                    <tr wire:key="defect-{{ $operatorId }}-{{ $process }}-{{ $type }}"
                        @class(['bg-gray-600' => $isSub])>
                        <td class="px-4 py-2 {{ $isSub ? 'pl-10 border-l-4 border-blue-300' : '' }}">
                            @if($isSub)↳ @endif{{ $operatorId }}
                        </td>
                        <td class="px-4 py-2">{{ $defect['operatorname'] ?? '' }}</td>
                        <td class="px-4 py-2">{{ $type }}</td>
                        <td class="px-4 py-2">{{ $qty > 0 ? $qty : '' }}</td>
                        <td class="px-4 py-2">{{ $defect['dateEncode'] ?? '' }}</td>
                        <td class="px-4 py-2">
                            {{ $process }}
                            @if($isSub)
                                <span class="ml-2 text-xs bg-blue-200 text-blue-900 px-2 py-0.5 rounded">
                                    Sub of {{ $defect['parentHfid'] ?? '-' }}
                                </span>
                            @endif
                        </td>
                    </tr>

                    <!-- SMALL DEFECT ROWS -->
                    @foreach($children as $sDefect)
                        <tr class="bg-gray-500"
                            wire:key="smalldefect-{{ $operatorId }}-{{ $process }}-{{ $type }}-{{ $sDefect['type'] ?? $loop->index }}">
                            <td class="px-8 py-1"></td>
                            <td class="px-8 py-1"></td>
                            <td class="px-6 py-1">↳ {{ $sDefect['type'] ?? '' }}</td>
                            <td class="px-4 py-1">{{ $sDefect['qty'] ?? '' }}</td>
                            <td class="px-4 py-1"></td>
                            <td class="px-4 py-1"></td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">
                            No defects added yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="flex-col w-11/12 sm:w-1/3 mt-3">
            <label for="TotalNG" class="block text-sm font-medium text-black">Total NG</label>
            <input type="text" id="TotalNG" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " required wire:model="totalNG" readonly>
        </div>
    </div>
</x-ui.wrapper.card-wrapper>