<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Models\mainDb\Inventory;
use App\Models\mainDb\Weighing;
use App\Models\PPFHeader\Seihin;

class InventoryService{
     public function update(
        string $partNo,
        int $ppfNo,
        string $action,
        int $goodQty,
        int $moldQty, // totalQty
        int $internalPartNo,
        string $prototypeFlag,
        string $group
    ): void {

        $inventory = Inventory::firstOrNew([
            '区分' => '1',
            '品番' => $partNo,
            '年月' => '000000',
        ]);

        if (! $inventory->exists) {
            $inventory->社内品番 = $internalPartNo;
            $inventory->{'ｸﾞﾙｰﾌﾟ'} = $group;
            $inventory->試作FLG = $prototypeFlag;
            $inventory->完品 = 0;
            $inventory->未検査 = 0;
            $inventory->未仕上 = 0;
            $inventory->販単 = null;
            $inventory->通貨FLG = null;
            $inventory->棚卸日 = null;
        }

        switch ($action) {

            case 'add':
                $inventory->未検査 += $goodQty;
                $inventory->未仕上 -= $moldQty;
                break;

            case 'edit':

                $weighing = Weighing::where('流動NO', $ppfNo)->firstOrFail();

                $inventory->未検査 -= ($weighing->合格数 - $goodQty);
                $inventory->未仕上 += ($weighing->総数 - $moldQty);

                break;

            case 'delete':

                $weighing = Weighing::where('流動NO', $ppfNo)->firstOrFail();

                $inventory->未検査 -= $weighing->合格数;
                $inventory->未仕上 += $weighing->総数;

                break;
        }

        $inventory->更新日 = now();

        $inventory->save();
    }
}