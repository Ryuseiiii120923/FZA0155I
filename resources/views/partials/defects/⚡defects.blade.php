<?php

use App\Repositories\Defect\DefectRepository;
use App\Services\Defect\DefectStagingService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Attributes\Locked;

new class extends Component
{
    public Collection $largeDefectMaster;
    public array $defects = [];
    public array $smallDefects = [];
    public array $staged = [];
    public ?string $modalSelectedType = null;
    public string  $modalLargeQty    = '';
    public array   $modalSmallDefects = [];
    public ?string $editingType      = null;
    public ?string $editingTypeSmall = null;
    public ?string $editingLarge     = null;

    #[Validate('required|numeric|min:1', message: 'Please enter a quantity.')]
    public string $newQuan = '';

    public string $newSmallQuan = '';

    public int $totalNg       = 0;
    public int $totalSmallQty = 0;

    public ?string $formId         = null;
    public ?string $dispatchPrefix = null;
     #[Locked]
    public bool $readonly = false;

    public function repository(){
        return app(DefectRepository::class);
    }

    public function staging(){
        return app(DefectStagingService::class);
    }

    public function mount(
        DefectRepository $repository,
        ?string $dispatchPrefix    = null,
        ?string $formId            = null,
        array   $loadedDefects     = [],
        array   $loadedSmallDefects = [],
        bool $readonly = false
    ): void {
        $this->readonly = $readonly;
        $this->formId              = $formId;
        $this->dispatchPrefix      = $dispatchPrefix;
        $this->largeDefectMaster   = $repository->getLargeDefects();
        $this->bootDefects($loadedDefects, $loadedSmallDefects);
    }

     #[On('read-only')]
    public function readOnly(bool $readonly = false){
        $this->readonly = $readonly;
    }

    #[On('FetchDefect')]
    public function onFetchDefect(array $data): void
    {
        $this->defects = $data['defects'] ?? [];

        // Normalise small defects shape from the event payload
        $this->smallDefects = collect($data['smallDefects'] ?? [])
            ->map(fn($items) => collect($items)
                ->map(fn($item) => [
                    'type' => $item['type'] ?? null,
                    'qty'  => $item['qty']  ?? '',
                ])
                ->toArray()
            )
            ->toArray();

        $this->syncTotals();
    }

    #[On('ClearForm')]
    public function onClearForm(?string $formId = null): void
    {
        if ($formId !== null && $formId !== $this->formId) return;

        $this->defects      = [];
        $this->smallDefects = [];
        $this->totalNg      = 0;
        $this->totalSmallQty = 0;
    }

    public function selectLargeDefect(string $type): void
    {
        if ($this->modalSelectedType !== null && $this->modalSelectedType !== $type) {
            $this->tryAutoStage($this->modalSelectedType);
        }
        if ($this->modalSelectedType === $type) {
            $this->clearModalSelection();
            return;
        }

        $this->modalSelectedType = $type;
        $this->modalLargeQty     = '';

        $this->modalSmallDefects = $this->repository()
            ->getSmallDefectsFor($type)
            ->map(fn($s) => ['type' => $s->SmallDefect, 'qty' => ''])
            ->values()
            ->toArray();


        $this->prefillModalFromStaged($type) || $this->prefillModalFromCommitted($type);
    }

    public function stageDefect(): void
    {
        $this->validate([
            'modalSelectedType' => 'required|string',
            'modalLargeQty'     => 'required|numeric|min:1',
        ], [
            'modalSelectedType.required' => 'Please select a defect type.',
            'modalLargeQty.required'     => 'Please enter a quantity.',
            'modalLargeQty.min'          => 'Quantity must be at least 1.',
        ]);

        $result = $this->staging()->buildStagedEntry(
            $this->modalSelectedType,
            (int) $this->modalLargeQty,
            $this->modalSmallDefects
        );

        if (! $result['ok']) {
            $this->addError('modalSmallDefects', $result['error']);
            return;
        }

        $this->staged = $this->staging()->upsertStagedDefect($this->staged, $result['entry']);
        $this->clearModalSelection();
        $this->resetErrorBag();
    }

    public function removeStagedDefect(string $type): void
    {
        $this->staged = $this->staging()->removeStagedDefect($this->staged, $type);
    }

    public function cancelDefectModal(): void
    {
        $this->staged = [];
        $this->clearModalSelection();
        $this->resetErrorBag();
    }


    public function confirmDefects(): void
    {
        if ($this->modalSelectedType !== null && (int) $this->modalLargeQty >= 1) {
            $this->tryAutoStage($this->modalSelectedType);
        }

        if (empty($this->staged)) {
            $this->validate([
                'modalSelectedType' => 'required|string',
                'modalLargeQty'     => 'required|numeric|min:1',
            ], [
                'modalSelectedType.required' => 'Please select and configure at least one defect.',
                'modalLargeQty.required'     => 'Please enter a quantity.',
                'modalLargeQty.min'          => 'Quantity must be at least 1.',
            ]);
            return;
        }

        $merged             = $this->staging()->mergeStagedIntoDefects(
            $this->staged,
            $this->defects,
            $this->smallDefects,
            $this->largeDefectMaster
        );
        $this->defects      = $merged['defects'];
        $this->smallDefects = $merged['smallDefects'];
        $this->syncTotals();

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects'      => $this->defects,
            'smallDefects' => $this->smallDefects,
            'formId'       => $this->formId,
            'action'       => 'add',
        ]);

        $this->broadcastNg();
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->staged = [];
        $this->clearModalSelection();
        $this->resetErrorBag();
        $this->dispatch('defect-confirmed');
    }

    /** Pre-populate staging from committed data so user can review/edit them. */
    public function reviewCommittedDefects(): void
    {
        $this->staged = [];

        foreach ($this->defects as $defect) {
            $type = $defect['type'] ?? null;
            if (! $type) continue;

            $this->staged[] = [
                'type' => $type,
                'qty'  => (int) ($defect['qty'] ?? 0),
                'smallDefects' => collect($this->smallDefects[$type] ?? [])
                    ->map(fn($s) => ['type' => $s['type'], 'qty' => (int) ($s['qty'] ?? 0)])
                    ->values()
                    ->toArray(),
            ];
        }
    }

    // ----------------------------------------------------------
    // Inline edit/delete — large defects
    // ----------------------------------------------------------

    public function startEditDefect(string $type): void
    {
        $this->editingType = $type;
        $defect = collect($this->defects)->firstWhere('type', $type);
        if ($defect) {
            $this->newQuan = (string) $defect['qty'];
        }
    }

    public function updateDefect(): void
    {
        $result = $this->staging()->updateLargeDefectQty(
            $this->defects,
            $this->smallDefects,
            $this->editingType,
            (float) $this->newQuan
        );
        $this->defects      = $result['defects'];
        $this->smallDefects = $result['smallDefects'];

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => [['type' => trim($this->editingType), 'qty' => $this->newQuan]],
            'formId'  => $this->formId,
            'action'  => 'update',
        ]);

        $this->broadcastNg();
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->editingType = null;
        $this->newQuan     = '';
    }

    public function deleteDefect(string $type): void
    {
        $result = $this->staging()->removeDefect($this->defects, $this->smallDefects, $type);
        $this->defects      = $result['defects'];
        $this->smallDefects = $result['smallDefects'];
        $this->syncTotals();

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => [['type' => $type, 'qty' => 0]],
            'action'  => 'delete',
            'formId'  => $this->formId,
        ]);

        $this->dispatch('NeedToDeleteDefect', ['formId' => $this->formId, 'type' => $type]);
        $this->broadcastNg();
        $this->dispatch('isDropdownUpdate', $this->formId);
    }

    // ----------------------------------------------------------
    // Inline edit/delete — small defects
    // ----------------------------------------------------------

    public function startEditSmallDefect(string $largeType, string $smallType): void
    {
        $this->editingLarge     = $largeType;
        $this->editingTypeSmall = $smallType;

        $small = collect($this->smallDefects[$largeType] ?? [])
            ->first(fn($s) => strtolower(trim($s['type'])) === strtolower(trim($smallType)));

        $this->newSmallQuan = $small ? (string) $small['qty'] : '';
    }

    public function updateSmallDefect(): void
    {
        $this->smallDefects = $this->staging()->updateSmallDefectQty(
            $this->smallDefects,
            $this->defects,
            $this->editingLarge,
            $this->editingTypeSmall,
            (float) $this->newSmallQuan
        );

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => $this->smallDefects,
            'formId'       => $this->formId,
            'action'       => 'update',
        ]);
        $this->dispatch('isDropdownUpdate', $this->formId);

        $this->editingLarge     = null;
        $this->editingTypeSmall = null;
        $this->newSmallQuan     = '';
    }

    public function deleteSmallDefect(string $largeType, string $smallType): void
    {
        $this->smallDefects = $this->staging()->removeSmallDefect(
            $this->smallDefects,
            $largeType,
            $smallType
        );

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => [$largeType => [['type' => $smallType, 'qty' => 0]]],
            'formId'       => $this->formId,
            'action'       => 'delete',
        ]);

        $this->dispatch('NeedToDeleteSmall', [
            'formId'      => $this->formId,
            'type'        => $smallType,
            'largeDefect' => $largeType,
        ]);
        $this->dispatch('isDropdownUpdate', $this->formId);
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function bootDefects(array $defects, array $smallDefects): void
    {
        $this->defects      = $defects;
        $this->smallDefects = $smallDefects;
        $this->syncTotals();
    }

    private function syncTotals(): void
    {
        $this->totalNg      = $this->staging()->calculateTotalNg($this->defects);
        $this->totalSmallQty = $this->staging()->calculateTotalSmallQty($this->smallDefects);
    }

    private function broadcastNg(): void
    {
        $this->dispatch('sendNg', $this->totalNg);
        $this->dispatch($this->dispatchPrefix . '.FetchNgDefectDropdown', [
            'formId'   => $this->formId,
            'defectNg' => $this->totalNg,
        ]);
    }

    private function clearModalSelection(): void
    {
        $this->modalSelectedType = null;
        $this->modalLargeQty     = '';
        $this->modalSmallDefects = [];
    }

    /** Stage the current modal entry silently (no validation error on failure). */
    private function tryAutoStage(string $type): void
    {
        $result = $this->staging()->buildStagedEntry(
            $type,
            (int) $this->modalLargeQty,
            $this->modalSmallDefects
        );
        if ($result['ok']) {
            $this->staged = $this->staging()->upsertStagedDefect($this->staged, $result['entry']);
        }
    }

    /** Returns true if pre-fill succeeded from staged data. */
    private function prefillModalFromStaged(string $type): bool
    {
        $entry = collect($this->staged)->firstWhere('type', $type);
        if (! $entry) return false;

        $this->modalLargeQty = (string) $entry['qty'];

        foreach ($this->modalSmallDefects as &$ms) {
            $saved = collect($entry['smallDefects'])->firstWhere('type', $ms['type']);
            if ($saved) $ms['qty'] = $saved['qty'];
        }
        unset($ms);

        return true;
    }

    /** Pre-fill from committed (saved) data. Always returns true for chaining clarity. */
    private function prefillModalFromCommitted(string $type): bool
    {
        $large = collect($this->defects)->firstWhere('type', $type);
        if ($large) {
            $this->modalLargeQty = (string) $large['qty'];
        }

        if (isset($this->smallDefects[$type])) {
            foreach ($this->modalSmallDefects as &$ms) {
                $saved = collect($this->smallDefects[$type])->firstWhere('type', $ms['type']);
                if ($saved) $ms['qty'] = $saved['qty'];
            }
            unset($ms);
        }

        return true;
    }
};
?>

@include('components.ui.defect-component.defect-section')