<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBillDetails;
use League\Fractal;

class PurchasedItemResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $bill;
    private $shippingStatus = [];
    private $paymenStatus;

    public function transform(UserBillDetails $data) {
        $this->data = $data;
        $this->bill = $data->mainBill;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $attr = @unserialize($this->data->item_details);
        $this->setPayStatusData();
        $this->return = [
            'itemOrderNumber' => $this->data->id,
            'itemName' => $this->data->item_name,
            'itemQuantity' => $this->data->quantity,
            'itemPrice' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->item_price),
            'itemImage' => $this->setItemImageData($this->data->item_image),
            'itemAttribute' => isset($attr['attributes']) ? $attr['attributes'] : [],
            'itemShippingStatus' => $this->setItemStatus($this->data),
            'itemEnableCancel' => $this->setItemCancelStatus($this->data),
            'itemEnableRefund' => $this->setItemRefundStatus($this->data),
            'itemCanceled' => $this->data->is_canceled ? $this->data->is_canceled : 0,
            'itemCancelDate' => $this->data->is_canceled && $this->data->cancel_date ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->cancel_date) : "",
            'itemRefunded' => $this->data->is_refund ? $this->data->is_refund : 0,
            'itemRefundDate' => $this->data->is_refund && $this->data->refund_date ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->refund_date) : "",
        ];
    }

    private function setPayStatusData() {
        $paymentStatusID = $this->bill->pay_status;
        if ($paymentStatusID > 0) {
            $this->paymenStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::where('id', $paymentStatusID)->first();
            if (!$this->paymenStatus) {
                throw new NotAcceptableHttpException(404);
            }
        }
    }

    private function setItemImageData($image) {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setItemStatus($userBillDetail) {
        $orderStatus = '';
        if (($this->paymenStatus && $this->paymenStatus->shipping_enabled) || ($this->data->pay_status == 0 && $this->data->voucher_used > 0) && isset($this->shippingStatus[$userBillDetail->id]) && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
            $this->shippingStatus[$userBillDetail->id] = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($userBillDetail->shipping_status);
            if ($this->shippingStatus[$userBillDetail->id]) {
                $orderStatus = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->shippingStatus[$userBillDetail->id], "name");
            }
        }
        return $orderStatus;
    }

    private function setItemCancelStatus($userBillDetail) {
        $cancelStatus = 0;
        if ((($this->paymenStatus && $this->paymenStatus->shipping_enabled) || ($this->data->pay_status == 0 && $this->data->voucher_used > 0)) && isset($this->shippingStatus[$userBillDetail->id]) && $this->shippingStatus[$userBillDetail->id]->cancel_enabled && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
            $cancelStatus = 1;
        }
        return $cancelStatus;
    }

    private function setItemRefundStatus($userBillDetail) {
        $refundStatus = 0;
        if ((($this->paymenStatus && $this->paymenStatus->shipping_enabled) || ($this->data->pay_status == 0 && $this->data->voucher_used > 0)) && isset($this->shippingStatus[$userBillDetail->id]) && $this->shippingStatus[$userBillDetail->id]->refund_enabled && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
            $refundStatus = 1;
        }
        return $refundStatus;
    }

}
