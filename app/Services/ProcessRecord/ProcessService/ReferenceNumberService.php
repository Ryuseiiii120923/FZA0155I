<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Models\mainDb\Control;
use Illuminate\Support\Facades\DB;

class ReferenceNumberService
{
    public function generate(): int
    {
        return DB::transaction(function (): int {

            $control = Control::where('区分', '計')
                ->lockForUpdate()
                ->firstOrFail();

            $nextNumber = $control->採番 + 1;

            if ($nextNumber > 9999999) {
                $nextNumber = 1;
            }

            $control->採番 = $nextNumber;
            $control->save();

            return $nextNumber;
        });
    }
}
