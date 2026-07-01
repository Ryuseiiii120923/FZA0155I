<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\PPFRepository;

use function PHPUnit\Framework\isEmpty;

class PPFService
{

    protected $ppfRepo;

    public function __construct(PPFRepository $ppfRepo)
    {
        $this->ppfRepo = $ppfRepo;
    }

    public function validateHeaderData(int $ppf, string $action): array
    {
        if ($ppf == null) {
      
            return ['error' => 'Please Enter PPF',];
        }
        $check = $this->ppfRepo->fetchHeadData($ppf);

        if ($action != 'view') {
            if (!$check) {
                return ['error' => 'PPF No does not encoded on Molding Result!',];
            }
            $checkProcessNo = $this->ppfRepo->getProcessNumber($check->品番 ?? "", $check->金型NO ?? "");

            if ((int)$checkProcessNo['pcNo'] < (int)$checkProcessNo['hfNo'] && (int)$checkProcessNo['pcNo'] <> 0) {
                if (!$this->ppfRepo->isExistinPostCure($ppf)) {
                    return ['error' => 'PPF is not yet encoded in Postcure Result!'];
                }
            }
        }

        return ['data' => $check];
    }

    public function fetchMainData(int $ppf, string $action)
    {

        $result = $this->validateHeaderData($ppf, $action);
        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }
        $check = $result['data'];
        $total_inspected = $this->ppfRepo->fetchTotalInspected($ppf) . '/' . (int)$check->未仕上;
        return [
            'partno' => $check->品番 ?? '',
            'lotno'  => $check->成形ﾛｯﾄ ?? '',
            'matno'  => $check->材料名 ?? 0,
            'moldno' => $check->金型NO ?? '',
            'shift'  => $check->班 ?? '',
            'opt'    => $check->作業員CD ?? '',
            'expct'  => $check->未仕上 ?? 0,
            'progressInsp' => $total_inspected
        ];
    }

    public function acceptProgressQty() {}
}
