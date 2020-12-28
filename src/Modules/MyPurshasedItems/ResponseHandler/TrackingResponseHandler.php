<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBill;
use OlaHub\UserPortal\Models\UserBillDetails;
use League\Fractal;

class TrackingResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $bill;
    private $shippingStatus = [];
    private $paymenStatus;

    public function transform(UserBill $data)
    {
        $this->data = $data;
        $this->bill = $data->billDetails;
        $this->setDefaultData();
        $this->setBillItems();
        return $this->return;
    }

    private function setDefaultData()
    {
        $payStatus = $this->setPayStatusData();
        $attr = @unserialize($this->data->item_details);
        $this->return = [
            "billNum" => isset($this->data->billing_number) ? $this->data->billing_number : NULL,
            "isTracking" => isset($this->data->is_tracking) ? $this->data->is_tracking : false,
            'billDate' => isset($this->data->billing_date) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDateTime($this->data->billing_date) : NULL,
            "billStatus" => isset($payStatus["name"]) ? $payStatus["name"] : "Fail",

        ];
        $this->setPayStatusData();
    }
    private function setPayStatusData()
    {
        $return = ["name" => "", "shipping" => 0];
        $paymentStatusID = $this->data->pay_status;
        if ($paymentStatusID > 0) {
            $this->paymenStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::where('id', $paymentStatusID)->first();
            if ($this->paymenStatus) {
                $return["name"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->paymenStatus, "name");
                $return["shipping"] = $this->paymenStatus->shipping_enabled;
            } else {
                throw new NotAcceptableHttpException(404);
            }
        } elseif ($paymentStatusID == 0) {
            $return = ["name" => "Paid", "shipping" => 1];
        }

        return $return;
    }

    private function setBillItems()
    {
        $userBillDetails = $this->bill;
        $itemsDetails = [];
        foreach ($userBillDetails as $userBillDetail) {
            $attr = @unserialize($userBillDetail->item_details);
            $shipping = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("review_enabled", "1")->find($userBillDetail->shipping_status);
            $itemsDetails[] = [
                'itemOrderNumber' => $userBillDetail->id,
                'itemName' => $userBillDetail->item_name,
                'itemImage' => $this->setItemImageData($userBillDetail->item_image),
                'itemShippingStatus' => $this->setItemStatus($userBillDetail),
                'itemTracking'=> $this->setTrackingOrder($userBillDetail->trackingItem)
            ];
        }
        $this->return["ItemsDetails"] = $itemsDetails;
    }
    private function setItemImageData($image)
    {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    private function setItemStatus($userBillDetail)
    {
        $orderStatus = 0;
        if ( isset($userBillDetail->shipping_status) && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
            $orderStatus = $userBillDetail->shipping_status;
        }
        return $orderStatus;
    }
    private function setTrackingOrder($values){
        $itemTrackingDetails = [];
        if($values) {
            foreach ($values as $value) {
                if($value->trackingStatus) {
                    $itemTrackingDetails[] = [
                        'trackingStatus' => isset($value->trackingStatus) ? $value->trackingStatus->name : '',
                        'trackingDate' => isset($value->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDateTime($value->created_at) : NULL

                    ];
                }
            }
        }
        return $itemTrackingDetails;
    }

}
