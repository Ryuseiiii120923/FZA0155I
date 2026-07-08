<?php

use App\Action\ProcessRecord\SavePpfAction;
use App\Services\ProcessRecord\DropDownService;
use App\Services\ProcessRecord\ProcessRecordService;
use App\Traits\HasNotifications;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Illuminate\Support\Facades\Log;

new class extends Component
{
    use HasNotifications;

    public array $forms = [];
    public bool $hasAnyForm = false;
    public array $hasErrorForm = [];
    public bool $hasError = false;
    public bool $isSaved = false;
    public string $hf_id             = '';
    public string $total_inspect     = '';
    public string $finishingProcedure = '';
    public array $modalOpen = [];
    public array $modalMode = [];
    public mixed $ppf = null;
    public int  $expectedQty = 0;
    public bool $isCheckPPF  = false;
    public int $encoder = 0;
    public array $dropdownForms = [];
    public array $reworkNg = [];
    public array $defectNg = [];
    public bool $readonly = false;

    protected $queryString = [
        'process' => ['except' => ''],
    ];

    public function service(): DropDownService
    {
        return app(DropDownService::class);
    }

    public function mount(): void
    {
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        foreach ($this->forms as $formId => $form) {
            $this->modalOpen[$formId] = false;
            $this->modalMode[$formId] = 'add';
        }
    }

    public function addNew(): void
    {
        $this->hasAnyForm = true;
        $formId = (string) Str::uuid();

        $this->forms[$formId] = [
            'hf_id'              => '',
            'formId'             => $formId,
            'hf_name'            => '',
            'finishingProcedure' => '',
            'total_inspect'      => '',
            'open'               => true,
            'defects'            => [],
            'smallDefects'       => [],
            'rework'             => [],
            'ForRework'          => false,
            'TotalNg'            => 0,
            'GoodQty'            => 0,
            'TotalRework'        => 0,
            'Remarks'            => '',
            'Operation'          => 'VI',
            'Process'            => '100% VI',
        ];

        $this->modalOpen[$formId] = true;
        $this->modalMode[$formId] = 'add';
        $this->resetModalFields();

        $this->dispatch('scroll-to-form', formId: $formId);
    }

    public function toggle(string $formId): void
    {
        if (isset($this->forms[$formId])) {
            $this->forms[$formId]['open'] = ! $this->forms[$formId]['open'];
        }
    }

    public function remove(string $formId): void
    {
        if (! isset($this->forms[$formId])) return;

        $this->dispatch('removeError', $formId);
        $this->dispatch('NeedToDeleteForm', $this->forms[$formId]);

        unset($this->forms[$formId], $this->modalOpen[$formId], $this->hasErrorForm[$formId]);
        $this->resetErrorBag('forms.' . $formId);

        // Re-index to force Livewire to re-render
        $this->forms = array_values($this->forms) === $this->forms
            ? $this->forms
            : [...$this->forms];
    }

    // ----------------------------------------------------------
    // HF modal (save / edit / close)
    // ----------------------------------------------------------

    public function openEditModal(string $formId): void
    {
        $this->modalMode[$formId] = 'edit';
        $this->modalOpen[$formId] = true;

        $this->hf_id              = $this->forms[$formId]['hf_id'] ?? '';
        $this->total_inspect      = (string) ($this->forms[$formId]['total_inspect'] ?? '');
        $this->finishingProcedure = $this->forms[$formId]['finishingProcedure'] ?? '';
    }

    public function closeModal(string $formId): void
    {
        $this->modalOpen[$formId] = false;
        $this->resetModalFields();
    }

    public function saveHF(string $formId): void
    {
        // Copy modal inputs into the form before passing to service
        $this->forms[$formId]['hf_id']             = $this->hf_id;
        $this->forms[$formId]['total_inspect']      = $this->total_inspect;
        $this->forms[$formId]['finishingProcedure'] = $this->finishingProcedure;

        try {
            $this->forms = $this->service()->saveHF(
                $formId,
                $this->forms,
                $this->hf_id,
                $this->total_inspect,
                $this->finishingProcedure
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            return;
        }

        $this->modalOpen[$formId] = false;

        $this->dispatch(
            'FetchHfNo',
            hf_id: $this->forms[$formId]['hf_id'],
            total_inspect: (int) $this->forms[$formId]['total_inspect'],
            form_id: $formId,
        );

        $this->calcGoodQty($formId);
        $this->resetModalFields();
    }

    public function cancelHF(string $formId): void
    {
        $this->modalOpen[$formId] = false;

        // If the form was never saved (no hf_id), remove it entirely
        if (empty($this->forms[$formId]['hf_id'] ?? '')) {
            unset($this->forms[$formId], $this->modalOpen[$formId]);
        }

        $this->resetModalFields();
    }

    public function checkHf(string $formId): void
    {
        if (isset($this->forms[$formId]) && !empty($this->hf_id)) {
            $this->forms[$formId]['hf_id'] = $this->hf_id;
        }

        $result = $this->service()->checkHf($formId, $this->forms);
        $this->forms = $result['forms'];

        $errorKey = 'forms.' . $formId . '.hf_id';

        if ($result['error']) {
            $this->addError($errorKey, $result['error']);
            $this->hasErrorForm[$formId] = true;
        } else {
            $this->resetErrorBag($errorKey);
            $this->hasErrorForm[$formId] = false;
        }

        $this->hasError = in_array(true, $this->hasErrorForm, strict: true);
        $this->dispatch('hasErrorPren', [
            'message'      => $result['error'],
            'hasError'     => $this->hasError,
            'hasErrorForm' => $this->hasErrorForm,
        ]);
    }

    // ----------------------------------------------------------
    // Inbound events
    // ----------------------------------------------------------

    #[On('fetchppf')]
    public function onFetchPpf(mixed $data): void
    {
        $this->ppf = $data;
    }

    #[On('IsCheckPPF')]
    public function onIsCheckPPF(bool $data): void
    {
        $this->isCheckPPF = $data;
    }

    #[On('expected')]
    public function onExpectedQty(int $data): void
    {
        $this->expectedQty = $data;
    }

    #[On('ClearFormDropdown')]
    public function onClearForm(): void
    {
        $this->forms        = [];
        $this->hasAnyForm   = false;
        $this->modalOpen    = [];
        $this->modalMode    = [];
        $this->reworkNg     = [];
        $this->defectNg     = [];
    }

    /**
     * Child components (Defects, Reworks) dispatch this when their data changes.
     * We sync those changes into the matching form and recalculate GoodQty.
     */
    #[On('operator.defects-updated')]
    public function onDefectsUpdated(array $data = []): void
    {
        $this->isSaved = false;

        $this->forms = $this->service()->syncFormData(
            $this->forms,
            $data,
            fn($existing, $incoming, $action, $keyResolver)
            => $this->syncCollection($existing, $incoming, $action, $keyResolver)
        );

        $formId = $data['formId'] ?? null;

        if ($formId && isset($this->forms[$formId])) {
            $this->reworkNg[$formId] = collect($this->forms[$formId]['rework'] ?? [])->sum('qty');
            $this->calcGoodQty($formId);
        }
    }

    #[On('edit-ppf')]
    public function onEditPpf(mixed $ppf, mixed $encoder, bool $readonly = false): void
    {
        $this->readonly = $readonly;
        $this->defectNg = [];
        $data = app(ProcessRecordService::class)->fetchForms($ppf, $encoder);
        if (empty($data['forms'])) return;

        $this->hasAnyForm = true;
        $this->forms      = $data['forms'];
        $this->defectNg   = $data['defectNg'];
        $this->reworkNg   = $data['reworkNg'];

        foreach (array_keys($this->forms) as $id) {
            $this->checkHf($id);
            $this->calcGoodQty($id);
            $this->modalOpen[$id] = false;
            $this->modalMode[$id] = 'edit';
        }
    }

       #[On('read-only')]
    public function readOnly(bool $readonly = false){
        $this->readonly = $readonly;
    }

    private function calcGoodQty(string $formId): void
    {
        $result = $this->service()->calcGoodQty(
            $formId,
            $this->forms,
            $this->defectNg,
            $this->reworkNg
        );

        if ($result) {
            $this->forms[$formId]['GoodQty'] = $result['GoodQty'];
            $this->forms[$formId]['TotalNg']  = $result['TotalNg'];
            $this->forms[$formId]['TotalRework'] = $result['TotalRework'];
        }
    }

    private function resetModalFields(): void
    {
        $this->hf_id              = '';
        $this->total_inspect      = '';
        $this->finishingProcedure = '';
    }

    private function syncCollection(
        array    $existing,
        array    $incoming,
        string   $action,
        callable $keyBuilder
    ): array {
        $map = collect($existing)->keyBy($keyBuilder)->toArray();

        foreach ($incoming as $item) {
            $key = $keyBuilder($item);
            if (! $key) continue;

            if ($action === 'delete') {
                unset($map[$key]);
            } else {
                // 'add' and 'update' both upsert
                $map[$key] = $item;
            }
        }

        return array_values($map);
    }

    public function saveRemarks($formId)
    {
        $remarks = $this->forms[$formId]['Remarks'] ?? '';

        $this->forms[$formId]['Remarks'] = trim($remarks);
    }

    #[On('ppf-checked')]
    public function ppfCheck(int $ppf)
    {
        $this->ppf = $ppf;
    }

    public function save(): void
    {
        if (empty($this->forms)) {
            $this->notifyFail('Forms', 'No forms to save.');
            return;
        }

        if (empty($this->ppf) || $this->ppf == 0) {
            $this->notifyFail('Error', 'Please Enter PPF');
            return;
        }

        try {
            app(SavePpfAction::class)->execute(
                forms: $this->forms,
                ppf: $this->ppf,
                encoder: $this->encoder,
                pendingDeletes: $this->pendingDeletes
            );
            $this->isSaved = true;
            $this->notifyReload('success', 'The Data has been saved.');
        } catch (\Throwable $e) {
            Log::error('Error saving data', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', type: 'error', message: 'Something went Wrong');
        }
    }

    //For deleting data

    public array $pendingDeletes = [
        'defects'      => [],
        'smallDefects' => [],
        'reworks'      => [],
        'forms'        => [],
    ];

    #[On('NeedToDeleteDefect')]
    public function onDeleteDefect(array $data): void
    {
        if (!empty($this->forms[$data['formId']]['id'] ?? null)) {
            $this->pendingDeletes['defects'][] = $data;
        }
    }

    #[On('NeedToDeleteForm')]
    public function onDeleteForm(array $form): void
    {
        if (!empty($form['id'] ?? null)) {
            $this->pendingDeletes['forms'][] = $form;
        }
    }

    #[On('NeedToDeleteSmall')]
    public function onDeleteSmall(array $data): void
    {
        if (!empty($this->forms[$data['formId']]['id'] ?? null)) {
            $this->pendingDeletes['smallDefects'][] = $data;
        }
    }

    #[On('NeedToDeleteRework')]
    public function onDeleteRework(array $data): void
    {
        if (!empty($this->forms[$data['formId']]['id'] ?? null)) {
            $this->pendingDeletes['reworks'][] = $data;
        }
    }
};
?>

<div>
    @include('components.ui.dropdown.dropdown-section')
    
    @unless($readonly)
    <div class="flex justify-center">
        <button
            type="button"
            wire:click="save"
            class="mt-4 px-10 py-3 cursor-pointer rounded-xl bg-[#0F3C89] text-white text-sm font-medium hover:bg-blue-800 transition disabled:opacity-50">
            Save
        </button>
    </div>
    @endunless
</div>