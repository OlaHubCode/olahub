<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBill;
use League\Fractal;

class PurchasedItemsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $shippingStatus = [];
    private $paymenStatus;

    public function transform(UserBill $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setBillItems();
        return $this->return;
    }

    private function setDefaultData() {
        $country = \OlaHub\UserPortal\Models\Country::where('id', $this->data->country_id)->first();
        $payData = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPayUsed($this->data);
        $payStatus = $this->setPayStatusData();
        $this->return = [
            "billNum" => isset($this->data->billing_number) ? $this->data->billing_number : NULL,
            "billPaidBy" => isset($payData) ? $payData : NULL,
            "billCurrency" => isset($this->data->billing_currency) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($this->data->billing_currency) : NULL,
            "billPaidFor" => isset($this->data->pay_for) ? $this->data->pay_for : 0,
            "celebration" => $this->setCelebration(),
            "billIsGift" => isset($this->data->is_gift) ? $this->data->is_gift : 0,
            "billTotal" => isset($this->data->billing_total) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->billing_total) : NULL,
            "billFees" => isset($this->data->billing_fees) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->billing_fees) : NULL,
            "billVoucher" => isset($this->data->voucher_used) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->voucher_used) : 0,
            "billVoucherAfter" => isset($this->data->voucher_after_pay) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->voucher_after_pay) : 0,
            "orderAddress" => isset($this->data->order_address) ? unserialize($this->data->order_address) : [],
            "billCountryName" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
            "billDate" => isset($this->data->billing_date) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDateTime($this->data->billing_date) : NULL,
            "billStatus" => isset($payStatus["name"]) ? $payStatus["name"] : "Fail",
            "billShippingEnabled" => isset($payStatus["shipping"]) ? $payStatus["shipping"] : 0,
        ];
    }

    private function setPayStatusData() {
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

    private function setItemImageData($image) {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setCelebration() {
        $celebrationName = null;
        if ($this->data->pay_for > 0) {
            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::find($this->data->pay_for);
            if ($celebration) {
                $celebrationName = $celebration->title;
            }
        }
        return $celebrationName;
    }

    private function setItemStatus($userBillDetail) {
        $orderStatus = '';
        if (($this->paymenStatus && $this->paymenStatus->shipping_enabled) || ($this->data->pay_status == 0 && $this->data->voucher_used > 0)  && isset($this->shippingStatus[$userBillDetail->id]) && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
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

    private function setBillItems() {
        $userBillDetails = \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $this->data->id)->get();
        $itemsDetails = [];
        foreach ($userBillDetails as $userBillDetail) {
            $attr = @unserialize($userBillDetail->item_details);
            $shipping = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("review_enabled", "1")->find($userBillDetail->shipping_status);
            $existReview = \OlaHub\UserPortal\Models\ItemReviews::where('item_id', $userBillDetail->item_id)->where('item_type', $userBillDetail->item_type)->first();
            $itemsDetails[] = [
                'itemOrderNumber' => $userBillDetail->id,
                'itemName' => $userBillDetail->item_name,
                'itemQuantity' => $userBillDetail->quantity,
                'itemPrice' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userBillDetail->item_price),
                'itemImage' => $this->setItemImageData($userBillDetail->item_image),
                'itemAttribute' => isset($attr['attributes']) ? $attr['attributes'] : [],
                'itemShippingStatus' => $this->setItemStatus($userBillDetail),
                'itemEnableCancel' => $this->setItemCancelStatus($userBillDetail),
                'itemEnableRefund' => $this->setItemRefundStatus($userBillDetail),
                'itemCanceled' => $userBillDetail->is_canceled ? $userBillDetail->is_canceled : 0,
                'itemCancelDate' => $userBillDetail->is_canceled && $userBillDetail->cancel_date ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($userBillDetail->cancel_date) : "",
                'itemRefunded' => $userBillDetail->is_refund ? $userBillDetail->is_refund : 0,
                'itemRefundDate' => $userBillDetail->is_refund && $userBillDetail->refund_date ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($userBillDetail->refund_date) : "",
                'itemEnableReview' => $shipping ? true : false,
                'itemExistReview' => $existReview ? true : false,
                'itemReviewData' => $existReview ? $this->setReviewInfo($existReview) : [],
            ];
        }

        $this->return["ItemsDetails"] = $itemsDetails;
    }
    
    private function setReviewInfo($review) {
        $info = [
            "userRate" => isset($review->rating) ? $review->rating : 0,
            "userReview" => isset($review->review) ? $review->review : '',
        ];
        
        return $info;
    }

}
