<?php

use App\Services\Dashboard\TotalInspectionService;
use App\Services\Helper\DashboardDraftService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public array $inspections = [];
    public int $ppf = 0;
    public bool $readonly = false;

    #[On('ppf-checked')]
    public function fetchInspection(int $ppf)
    {

        $this->ppf = $ppf;
        $this->inspections = app(TotalInspectionService::class)->fetchTotalInspection($ppf);
        $this->syncDraft();
    }

    protected function syncDraft(): void
    {
        $insps = collect($this->inspections)
            ->pluck('hfid')
            ->filter()
            ->unique()
            ->values();

        $inspection = [
            'insp1' => $insps->get(0, ''),
            'insp2' => $insps->get(1, ''),
            'insp3' => $insps->get(2, ''),
            'insp4' => $insps->get(3, ''),
            'insp5' => $insps->get(4, ''),
        ];

        app(DashboardDraftService::class)->put($this->ppf, 'inspectors', [
            'inspections' => [$inspection],
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
    $this->inspections = [];
}
};
?>

<div class=" bg-white shadow-lg px-3 py-4 @if($readonly) opacity-50 cursor-not-allowed @endif">
    <div class="bg-gray-700 w-full ">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Total Inspection</p>
    </div>
    <div class="overflow-x-auto mt-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Inspector Id</th>
                    <th class="px-4 py-2">Inspector Name</th>
                    <th class="px-4 py-2">Total Inspection</th>
                    <th class="px-4 py-2">Date Encode</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $inspections ?? [] as $inspection )
                <tr>
                    <td class="px-4 py-2">{{ $inspection['hfid'] ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection['hfname'] ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection['totalInspect'] ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $inspection['dateEncode'] ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center">
                        No data added.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>