<?php

namespace App\Services\ProcessRecord\ProcessService;

use App\Models\PPFHeader\Seihin;

class ProcessService
{
    public function __construct(
        protected ReferenceNumberService $referenceNumberService,
        protected InventoryService $inventoryService,
        protected MoldingResultService $moldingResultService,
        protected InspectionSlipService $inspectionSlipService,
        protected WeighingService $weighingService,
    ) {}

    public function process(array $data)
    {   
        $ppf = $data['ppf'];
        $partNo = $data['partNo'];
        $action = $data['action'];
        $goodQty = $data['goodQty'];
        $moldQty = $data['moldQty']; // totalQty
        $lotNo = $data['lotNo'];
        $inspectionDate = $data['inspectionDate'];
        $encoder = $data['encoder'];


        $refNo = $this->referenceNumberService->generate();
         $product = Seihin::where('品番', $partNo)->firstOrFail();
         $internalPartNo = $product->社内品番;
         $prototypeFlag = $product->試作FLG;
         $group = $product->ｸﾞﾙｰﾌﾟ;
         $inspectionType = $product->現品票区;

        $this->inventoryService->update($partNo, $ppf, $action, $goodQty, $moldQty,$internalPartNo,$prototypeFlag,$group);

        $this->moldingResultService->update( $ppf, $action, $goodQty, $moldQty);

        $this->inspectionSlipService->handle($ppf,$partNo,$internalPartNo,$inspectionType,$lotNo,$goodQty,$action);

        $this->weighingService->handle($action,$ppf,$refNo,$partNo,$internalPartNo,$lotNo, $moldQty,$goodQty,$inspectionDate,$encoder);
    }
}