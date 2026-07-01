<?php

namespace App\Action\HFRW;

use App\Repositories\HFRW\hfrwRepository;
use Illuminate\Support\Facades\DB;

class SaveHfrwAction
{
    public $repo;
    public function __construct(hfrwRepository $repo){
        $this->repo = $repo;
    }

    public function save($data)
    {
        return DB::transaction(function () use ($data) {
            foreach ($data['forms'] as $form) {

                $hfId = $form['hf_id'];
                $this->repo->saveMainForm([
                    'hfid' => $hfId,
                    'total_inspect' => $form['total_inspect'],
                    'encoder' => $data['encoder'],
                    'ppfno' => $data['ppfno'],
                    'goodQty' => $form['GoodQty'] ?? 0,
                    'inspect_REC' => $form['inspect_REC'],
                    'reworkNo' => $data['reworkNo']
                ]);
                $this->repo->saveDefects(
                    $hfId,
                    $form['defects'] ?? [],
                    $data['ppfno'],
                    $data['encoder'],
                    $form['inspect_REC'],
                    $data['reworkNo']
                );

                $this->repo->saveSmallDefects(
                    $hfId,
                    $form['smallDefects'] ?? [],
                    $data['ppfno'],
                    $data['encoder'],
                    $form['inspect_REC'],
                    $data['reworkNo']
                );
            }

            // ✅ update flag once per PPF
            $this->repo->updateFlag($data['ppfno'], $data['reworkNo']);
        });
    }
}
