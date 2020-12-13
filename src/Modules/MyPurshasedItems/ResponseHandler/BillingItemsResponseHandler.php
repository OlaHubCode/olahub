<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBill;
use OlaHub\UserPortal\Models\UserBillDetails;
use League\Fractal;

class BillingItemsResponseHandler extends Fractal\TransformerAbstract
{
    private $return;
    private $data;
    private $bill;

    public function transform(UserBillDetails $data)
    {
        $this->data = $data;
        $this->bill = UserBill::withoutGlobalScope("currntUser")->where('id',$this->data->billing_id)->first();
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $this->return = [
            'itemOrderNumber' => $this->data->id,
            'itemName' => $this->data->item_name,
            'itemImage' => $this->setItemImageData($this->data->item_image),
        ];
    }

    private function setItemImageData($image)
    {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

}
