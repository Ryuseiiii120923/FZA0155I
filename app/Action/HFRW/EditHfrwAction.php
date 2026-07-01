<?php

namespace App\Action\HFRW;

use App\Repositories\HFRW\hfrwRepository;
use Illuminate\Support\Facades\Log;

class EditHfrwAction
{
    public function __construct(protected hfrwRepository $doneReworkRepo)
    {
    }
    public function edit(array $data, array $needdeleteSmall = [], array $needdeleteDefect = [], array $needdeleteForm = [])
    {

        $ppfno = $data['ppfno'];
        try {
            if (!empty($needdeleteForm)) {
                foreach ($needdeleteForm as $form) {
                        $this->doneReworkRepo->deleteForm($form['formId'], $ppfno);
                }
            }
            if (!empty($needdeleteDefect)) {
                foreach ($needdeleteDefect as $defect) {
                    $type = $defect['type'];
                    $formId = $defect['formId'];

                    if ($formId) {
                        $this->doneReworkRepo->deleteLargeDefect($ppfno, $type, $formId);
                    }
                }
            }

            if (!empty($needdeleteSmall)) {
                foreach ($needdeleteSmall as $small) {
                    $large = $small['largeDefect'];
                    $type = $small['type'];
                    $formId = $small['formId'];
                    $this->doneReworkRepo->deleteSmallDefect($ppfno, $large, $type, $formId);
                }
            }
            app(SaveHfrwAction::class)->save($data);
        } catch (\Exception $e) {
            Log::error('Edit PR Encode Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine()
            ]);
        }
    }
}