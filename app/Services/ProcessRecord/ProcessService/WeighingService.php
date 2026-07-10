<?php

namespace   App\Services\ProcessRecord\ProcessService;

use App\Models\mainDb\Weighing;

class WeighingService{
        public function handle(
    string $action,
    int $ppfNo,
    int $registrationNo,
    string $partNo,
    string $internalPartNo,
    string $lotNo,
    int $moldQty,
    int $goodQty,
    string $finishDate,
    int $employeeId
): void {

    if ($action === 'delete') {

        Weighing::where('流動NO', $ppfNo)->delete();

        return;
    }

    $data = [
        '登録NO'   => $registrationNo,
        '社員CD'   => 0,
        '作業員CD' => 0,
        '流動NO'   => $ppfNo,
        '仕上日'   => $finishDate,
        '計量日'   => now(),
        '品番'     => $partNo,
        '社内品番' => $internalPartNo,
        'ﾛｯﾄNO'    => $lotNo,
        '総数'     => $moldQty,
        '合格数'   => $goodQty,
        '単価係数' => 1,
        '再検'     => '1',
        '移動'     => '',
        '入力日'   => today(),
        '登録者'   => $employeeId,
        'タイム'   => now()->format('H:i:s'),
    ];

    if ($action === 'add') {

        // Matches the VB.NET behavior of deleting before inserting.
        Weighing::where('流動NO', $ppfNo)->delete();

        Weighing::create($data);

        return;
    }

    // edit
    Weighing::where('流動NO', $ppfNo)->update($data);
}
}