<?php

use App\Action\HFRW\DeleteHfrwAction;
use App\Action\HFRW\EditHfrwAction;
use App\Action\HFRW\SaveHfrwAction;
use App\Services\HFRW\HfrwService;
use App\Services\ProcessRecord\DropDownService;
use App\Traits\HasNotifications;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;
    use HasNotifications;
    public bool $toggles = false;
    public array $modalOpen = [];
    public array $forms = [];
    public ?int $selectedPPF = null;
    public bool $hasError = false;
    public array $hasErrorForm = [];
    public array $defectNg = [];
    public array $reworkNg = [];
    public string $hf_id = '';
    public string $total_inspect = '';
    public bool $isEdit = false;
    public $open;
    public mixed $encoder;
    public int $reworkNo = 0;
    public $inspectRec = [];
    public $needdeleteSmall = [], $needdeleteDefect = [], $needdeleteForm = [];
    public string $search = "";

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function mainService(): HfrwService
    {
        return app(HfrwService::class);
    }

    public function mount()
    {
        $this->encoder = Auth::user()->社員CD;
    }

    private function dropService(): DropDownService
    {
        return app(DropDownService::class);
    }

    #[Computed]
    public function pendingRework()
    {
        return $this->mainService()->fetchPendingRework($this->search);
    }

    public function addNew(): void
    {
        $formId = (string) Str::uuid();
        $this->forms[$formId] = $this->mainService()->buildEmptyForm($this->selectedPPF);
        $this->modalOpen[$formId] = true;
    }

    public function confirmPPF(int $ppf): void
    {
        $this->selectedPPF = $ppf;
    }

    public function toggle(string $formId): void
    {
        $this->forms[$formId]['open'] = !$this->forms[$formId]['open'];
    }

    public function removeSelectedPPF()
    {
        $this->selectedPPF = null;
        $this->forms = [];
        $this->modalOpen = [];
        $this->open = false;
    }

    public function checkHf(string $formId): void
    {
        if (isset($this->forms[$formId]) && !empty($this->hf_id)) {
            $this->forms[$formId]['hf_id'] = $this->hf_id;
        }

        $result = $this->dropService()->checkHf($formId, $this->forms);
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

    public function saveHF(string $formId): void
    {
        try {
            $this->dropService()->saveHF(
                $formId,
                $this->forms,
                $this->hf_id,
                $this->total_inspect
            );

            $this->modalOpen[$formId] = false;

            $this->dispatch(
                'FetchHfNo',
                hf_id: $this->forms[$formId]['hf_id'],
                total_inspect: (int) $this->forms[$formId]['total_inspect'],
                form_id: $formId
            );

            $this->calcGoodQty($formId);

            $this->hf_id = '';
            $this->total_inspect = 0;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function saveHFRework(): void
    {
        try {
            $forms = $this->mainService()->prepareFormsForSave(
                $this->forms,
                $this->selectedPPF
            );


            if (empty($forms)) {
                throw new \Exception('No forms to save.');
            }


            $data = [
                'ppfno'    => $this->selectedPPF,
                'encoder'  => $this->encoder ?? 'system',
                'forms'    => $forms,
                'reworkNo' => $this->reworkNo,
            ];

            if (!$this->isEdit) {
                app(SaveHfrwAction::class)->save($data);
            } else {
                app(EditHfrwAction::class)->edit(
                    $data,
                    $this->needdeleteSmall,
                    $this->needdeleteDefect,
                    $this->needdeleteForm
                );
                $this->isEdit = false;
            }

            $this->removeSelectedPPF();
            $this->notifyReload('success', 'Saved Successfully');
        } catch (\Throwable $e) {
            $this->notifyFail('error', $e->getMessage());
        }
    }

    public function edit(string $ppf, int $reworkNo): void
    {
        $this->selectedPPF = (int) $ppf;
        $this->isEdit = true;

        try {
            $result = $this->mainService()->fetchReworkDetails($ppf, $reworkNo, $this->encoder);
            $this->forms    = $result['forms'];
            $this->defectNg = $result['defectNg'];
            foreach ($this->forms as $formId => $form) {
                $this->modalOpen[$formId] = false;
                $this->calcGoodQty($formId);
                $this->inspectRec[$formId] = $form['inspect_REC'] ?? null;
            }
        } catch (\Throwable $e) {
            $this->notifyFail('Error', 'Failed to fetch forms for edit: ' . $e->getMessage());
        }
    }

    public function editHF($formId)
    {
        $this->modalOpen[$formId] = true;
    }

    public function calcGoodQty(string $formId): void
    {
        if (!isset($this->forms[$formId])) return;

        $result = $this->mainService()->calcGoodQty(
            $this->forms[$formId],
            $this->defectNg[$formId] ?? null,
            $this->reworkNg[$formId] ?? null,
        );

        $this->forms[$formId]['GoodQty']     = $result['goodQty'];
        $this->forms[$formId]['TotalNg']     = $result['totalNg'];
        $this->forms[$formId]['TotalRework'] = $result['reworkQty'];
    }

    public function delete(int $ppf, int $reworkNo)
    {
        try {
            app(DeleteHfrwAction::class)->delete($ppf, $reworkNo);
            $this->notifyReload('success', 'Deleted Successfully');
        } catch (\Throwable $e) {
            $this->notifyFail('Error', $e->getMessage());
        }
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

    #[On('hfrw.defects-updated')]
    public function fetchDefect(array $data = [])
    {
        $this->forms = $this->dropService()->syncFormData(
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

    public function remove($id)
    {
        $realRec = $this->inspectRec[$id] ?? null;
        if ($realRec) {
            $this->needdeleteForm[$realRec] = [
                'formId' => $realRec,
            ];
        }

        unset(
            $this->forms[$id],
            $this->modalOpen[$id],
            $this->inspectRec[$id],
        );
    }

    #[On('NeedToDeleteDefect')]
    public function deleteDefectFromChild($data)
    {
        $formId = $data['formId'];
        $type   = $data['type'];

        $realRec = $this->inspectRec[$formId] ?? null;
        if (!$realRec) return; // nothing persisted to delete

        $key = $realRec . '_' . strtolower(trim($type));

        $this->needdeleteDefect[$key] = [
            'formId' => $realRec,
            'type'   => $type,
        ];
    }

    #[On('NeedToDeleteSmall')]
    public function deleteSmallFromChild($data)
    {
        $formId      = $data['formId'];
        $type        = $data['type'];
        $largeDefect = $data['largeDefect'];

        $realRec = $this->inspectRec[$formId] ?? null;
        if (!$realRec) return;

        $key = $realRec . '_' . strtolower(trim($largeDefect)) . '_' . strtolower(trim($type));

        $this->needdeleteSmall[$key] = [
            'formId'      => $realRec,
            'type'        => $type,
            'largeDefect' => $largeDefect,
        ];
    }
};
?>

<div>
    @include('components.ui.hfrw.hf-rework-encoding.hf-rework-section')
</div>