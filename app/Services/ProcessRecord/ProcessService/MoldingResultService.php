<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Models\mainDb\MoldingResult;

class MoldingResultService{
    public function update(int $ppfNo, string $action, int $moldQty, int $goodQty): void
    {
        $result = MoldingResult::where('流動NO', $ppfNo)->first();

        if (! $result) {
            return;
        }

        if ($action === 'delete') {

            $result->成形合計 = null;
            $result->仕上実績 = null;
            $result->仕上完品 = null;
            $result->完納仕上 = null;

        } else {

            $result->成形合計 = $moldQty;
            $result->仕上実績 = $moldQty;
            $result->仕上完品 = $goodQty;
            $result->完納仕上 = 1;
        }

        $result->save();
    }

}