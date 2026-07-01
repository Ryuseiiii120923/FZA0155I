<?php

use App\Models\Rework\ReworkMaster;
use App\Services\Rework\ReworkStagingService;
use App\Services\Rework\ReworkValidationService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Attributes\Locked;

new class extends Component
{

    public array $committed = [];
    public array $staged = [];

    #[Validate('required|string|max:255', message: 'Please enter a rework type.')]
    public string $newType = '';

    #[Validate('required|numeric|min:1', message: 'Please enter a quantity.')]
    public string $newQuan = '';
    public string $currentHfno = '';
    public ?string $currentHfname = null;
    public int $totalInsp = 0;
    public int $totalNg = 0;

    public ?string $formId         = null;
    public ?string $dispatchPrefix = null;
    #[Locked]
    public bool $readonly = false;
    public ?string $editingType  = null;
    public ?string $editingHfno  = null;

    private function validation()
    {
        return app(ReworkValidationService::class);
    }

    private function staging()
    {
        return app(ReworkStagingService::class);
    }
    public function mount(
        ?string $dispatchPrefix = null,
        ?string $formId         = null,
        string  $hfNo           = '',
        array   $loadedRework   = [],
        int     $totalInsp      = 0,
        bool $readonly = false
    ): void {
        $this->readonly = $readonly;
        $this->formId         = $formId;
        $this->dispatchPrefix = $dispatchPrefix;
        $this->currentHfno = $hfNo;
        $this->totalInsp   = $totalInsp;
        $this->bootFromLoaded($loadedRework);
    }

      #[On('read-only')]
    public function readOnly(bool $readonly = false){
        $this->readonly = $readonly;
    }

    // ----------------------------------------------------------
    // Computed
    // ----------------------------------------------------------

    /** Master rework-type list used to populate the dropdown. */
    #[Computed]
    public function reworkMasterList()
    {
        return ReworkMaster::all();
    }

    // ----------------------------------------------------------
    // Inbound events
    // ----------------------------------------------------------

    /** Sibling (dropdown) tells us the HF code + total inspections for this form. */
    #[On('FetchHfNo')]
    public function onFetchHfNo(string $hf_id, int|string $total_inspect, string $form_id): void
    {
        if ($form_id !== $this->formId) return;

        $this->currentHfno = $hf_id;
        $this->totalInsp   = (int) $total_inspect;
    }

    /** Parent re-hydrates this component with saved rework data (e.g. on edit). */
    #[On('FetchRework')]
    public function onFetchRework(array $data): void
    {
        $formId = $data['formId'] ?? $this->formId;
        if ($formId !== $this->formId) return;

        $this->committed = $data['reworks'] ?? [];
        $this->totalNg   = $this->staging()->sumNg($this->committed);

        $this->dispatchNgToDropdown();
    }

    #[On('ClearForm')]
    public function onClearForm(?string $formId = null): void
    {
        // Clear only if targeted at this form, or broadcast to all
        if ($formId !== null && $formId !== $this->formId) return;

        $this->committed = [];
        $this->staged    = [];
        $this->totalNg   = 0;
    }

    public function checkHf(): void
    {
        if (empty(trim($this->currentHfno))) {
            $this->currentHfname = null;
            $this->resetErrorBag('currentHfno');
            return;
        }

        $result = $this->validation()->resolveOperator($this->currentHfno);

        if ($result['ok']) {
            $this->currentHfname = $result['name'];
            $this->resetErrorBag('currentHfno');
        } else {
            $this->addError($result['field'], $result['message']);
            $this->currentHfno   = '';
            $this->currentHfname = null;
        }
    }

    public function stageRework(): void
    {
        $this->validate();

        $checks = [
            $this->validation()->typeExistsInMaster($this->newType),
            $this->validation()->notAlreadyCommitted($this->committed, $this->currentHfno, $this->newType),
            $this->validation()->notAlreadyStaged($this->staged, $this->currentHfno, $this->newType),
            $this->validation()->withinHfLimit($this->committed, $this->staged, $this->currentHfno),
        ];

        foreach ($checks as $result) {
            if (! $result['ok']) {
                $this->addError($result['field'], $result['message']);
                return;
            }
        }

        $this->staged = $this->staging()->addEntry($this->staged, [
            'hfno'      => $this->currentHfno,
            'totalinsp' => $this->totalInsp,
            'type'      => $this->newType,
            'qty'      => (int) $this->newQuan,
        ]);

        $this->newType = '';
        $this->newQuan = '';
        $this->resetErrorBag();
    }

    public function removeStagedRework(int $index): void
    {
        $this->staged = $this->staging()->removeEntry($this->staged, $index);
    }

    /** Cancel — discard all staged rows and reset modal fields. */
    public function cancelReworkModal(): void
    {
        $this->staged  = $this->staging()->reset();
        $this->newType = '';
        $this->newQuan = '';
        $this->resetErrorBag();
    }


    public function confirmReworks(): void
    {

        if (empty($this->staged) && $this->newType !== '' && $this->newQuan !== '') {
            $this->stageRework();
            if ($this->getErrorBag()->isNotEmpty()) return;
        }

        if (empty($this->staged)) {
            $this->addError('newType', 'Please add at least one rework entry before confirming.');
            return;
        }

        $this->committed = $this->staging()->commitAll($this->committed, $this->staged);
        $this->staged    = $this->staging()->reset();
        $this->newType   = '';
        $this->newQuan   = '';

        $this->dispatchCommittedUpdate('add');
        $this->recalculateNg();
        $this->dispatch('rework-confirmed');
    }

    public function reviewCommittedReworks(): void
    {
        $this->staged = $this->staging()->loadFromCommitted($this->committed);
    }

    public function startEditRework(string $type, string $hfno): void
    {
        $this->editingType = $type;
        $this->editingHfno = $hfno;

        $row = collect($this->committed)->first(
            fn($r) => ($r['type'] ?? '') === $type && ($r['hfno'] ?? '') === $hfno
        );

        if ($row) {
            $this->currentHfno = $row['hfno'];
            $this->newQuan     = (string) $row['qty'];
            $this->totalInsp   = (int) ($row['totalinsp'] ?? 0);
        }
    }

    public function updateRework(): void
    {
        $this->committed = $this->staging()->updateCommitted(
            $this->committed,
            $this->editingType,
            $this->editingHfno,
            (int) $this->newQuan,
            $this->currentHfno,
            $this->totalInsp
        );

        $this->dispatchDefectsUpdated([
            [
                'hfno'      => $this->currentHfno,
                'type'      => $this->editingType,
                'qty'      => (int) $this->newQuan,
                'totalinsp' => $this->totalInsp,
            ],
        ], 'update');

        $this->editingType = null;
        $this->editingHfno = null;
        $this->newQuan     = '';
    }

    public function deleteRework(string $hfno, string $type): void
    {
        $hfno = trim($hfno);
        $type = trim(strtoupper($type));

        $this->committed = collect($this->committed)
            ->reject(
                fn($r) =>
                trim($r['hfno'] ?? '') === $hfno &&
                    trim(strtoupper($r['type'] ?? '')) === $type
            )
            ->values()
            ->toArray();

        $this->dispatch('NeedToDeleteRework', [
            'hfno'   => $hfno,
            'type'   => $type,
            'formId' => $this->formId,
        ]);

        $this->dispatchDefectsUpdated([['hfno' => $hfno, 'type' => $type]], 'delete');
        $this->recalculateNg();
    }

    private function bootFromLoaded(array $loaded): void
    {
        $this->committed = $loaded;
        $this->totalNg   = $this->staging()->sumNg($loaded);
    }

    private function recalculateNg(): void
    {
        $this->totalNg = $this->staging()->sumNg($this->committed);

        $this->dispatch($this->dispatchPrefix . '.FetchNgReworkDropdown', [
            'formId'        => $this->formId,
            'totalReworkNg' => $this->totalNg,
        ]);
    }

    private function dispatchNgToDropdown(): void
    {
        $this->dispatch($this->dispatchPrefix . '.FetchNgDropdown', [
            'formId'        => $this->formId,
            'totalReworkNg' => $this->totalNg,
        ]);
    }

    private function dispatchCommittedUpdate(string $action): void
    {
        $this->dispatchDefectsUpdated($this->committed, $action);
    }

    private function dispatchDefectsUpdated(array $reworksData, string $action): void
    {
        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => $reworksData,
            'formId'      => $this->formId,
            'action'      => $action,
        ]);
    }
};
?>

@include('components.ui.rework-component.rework-section')