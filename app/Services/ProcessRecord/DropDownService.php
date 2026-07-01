<?php

namespace App\Services\ProcessRecord;

use App\Models\Auth\Worker;
use App\Models\HFDetails\HFName;
use App\Repositories\ProcessRecord\DropDownRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DropDownService
{

    public function __construct(
        protected DropDownRepository $dropdownRepo,
    ) {}
    public function saveHF($formId, $forms, $hf_id, $total_inspect, $finishingProcedure = null)
    {
        $data = [
            'forms' => $forms,
        ];

        $validator = Validator::make(
            $data,
            [
                "forms.$formId.hf_id" => 'required',
                "forms.$formId.total_inspect" => 'required|numeric|min:1',
            ],
            [
                "forms.$formId.hf_id.required" => 'HF ID is required!',
                "forms.$formId.total_inspect.required" => 'Total Inspect is required!',
                "forms.$formId.total_inspect.numeric" => 'Total Inspect must be a number!',
                "forms.$formId.total_inspect.min" => 'Total Inspect must be at least 1!',
            ],
            [
                "forms.$formId.hf_id" => "HF ID",
                "forms.$formId.total_inspect" => "Total Inspect",
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Process data
        $forms[$formId]['hf_id'] = $forms[$formId]['hf_id'] ?? $hf_id;
        $forms[$formId]['total_inspect'] = $forms[$formId]['total_inspect'] ?? $total_inspect;

        if (!is_null($finishingProcedure)) {
            $forms[$formId]['finishingProcedure'] =
                $forms[$formId]['finishingProcedure'] ?? $finishingProcedure;
        }

        return $forms;
    }

    public function checkHf($formId, $forms)
    {
        if (!$formId || !isset($forms[$formId])) {
            return ['error' => null, 'forms' => $forms];
        }

        $currentHfId = $forms[$formId]['hf_id'] ?? null;

        if (empty($currentHfId)) {
            $forms[$formId]['hf_name'] = null;

            return [
                'error' => 'HF ID cannot be empty',
                'forms' => $forms
            ];
        }

        $searchValue = strlen($currentHfId) === 2
            ? ' ' . $currentHfId
            : $currentHfId;

        $hf = Worker::where('作業員CD', $searchValue)
            ->first();

        if (!$hf) {
            $forms[$formId]['hf_name'] = null;

            return [
                'error' => 'This Operator does not exist',
                'forms' => $forms
            ];
        }

        $name = HFName::where('社員CD', $hf->社員CD)->first();
        $forms[$formId]['hf_name'] = $name?->名前;
        return [
            'error' => null,
            'forms' => $forms
        ];
    }

    public function syncFormData(array $forms, array $data, callable $syncCollection): array
    {
        $formId = $data['formId'] ?? null;
        if (!$formId || !isset($forms[$formId])) {
            return $forms;
        }

        $action = $data['action'] ?? 'add';

        $forms[$formId]['defects'] ??= [];
        $forms[$formId]['smallDefects'] ??= [];
        $forms[$formId]['rework'] ??= [];

        // DEFECTS
        $forms[$formId]['defects'] = $syncCollection(
            $forms[$formId]['defects'],
            $data['defects'] ?? [],
            $action,
            fn($d) =>
            !empty($d['type'])
                ? strtolower(trim($d['type'])) . '_' . strtolower(trim($d['category'] ?? 'large'))
                : null
        );

        // SMALL DEFECTS
        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {
            $forms[$formId]['smallDefects'][$large] = $syncCollection(
                $forms[$formId]['smallDefects'][$large] ?? [],
                $smalls,
                $action,
                fn($s) => !empty($s['type']) ? strtolower(trim($s['type'])) : null
            );
        }

        // REWORKS
        $forms[$formId]['rework'] = $syncCollection(
            $forms[$formId]['rework'] ?? [],
            $data['reworksData'] ?? [],
            $action,
            fn($r) =>
            !empty($r['type'])
                ? strtolower(trim($r['type'])) . '_' . (int)($r['hfno'] ?? 0)
                : null
        );

        return $forms;
    }

    public function calcGoodQty($formId, $forms, $defectNg = [], $reworkNg = [])
    {
        if (!isset($forms[$formId])) {
            return null;
        }
        $form = $forms[$formId];

        $defectQty = isset($defectNg[$formId])
            ? $defectNg[$formId]
            : collect($form['defects'] ?? [])->sum('qty');

        $reworkQty = isset($reworkNg[$formId])
            ? $reworkNg[$formId]
            : collect($form['rework'] ?? [])->sum('qty');

        $totalNg = ($defectQty ?? 0) + ($reworkQty ?? 0);

        $forms[$formId]['GoodQty'] =
            ($form['total_inspect'] ?? 0) - $totalNg;

        $forms[$formId]['TotalNg'] = $totalNg;
        $forms[$formId]['TotalRework'] = $reworkQty;
        return [
            'GoodQty' => $forms[$formId]['GoodQty'],
            'TotalNg' => $forms[$formId]['TotalNg'],
            'TotalRework' => $forms[$formId]['TotalRework']
        ];
    }


}