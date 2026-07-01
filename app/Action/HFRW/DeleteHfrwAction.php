<?php 

namespace App\Action\HFRW;

use App\Repositories\HFRW\hfrwRepository;
use Illuminate\Support\Facades\DB;

class DeleteHfrwAction
{
    public function delete(int $ppf, int $reworkNo): bool
    {
        return DB::transaction(function () use ($ppf, $reworkNo) {
            $repo = app(HfrwRepository::class);

            $result = $repo->deleteDoneReworkByPPF($ppf, $reworkNo);

            if (!$result) {
                throw new \Exception("No rework found to delete for PPF: {$ppf}, ReworkNo: {$reworkNo}");
            }

            $repo->updateflagdoneforDelete($ppf, $reworkNo);

            return true;
        });
    }
}