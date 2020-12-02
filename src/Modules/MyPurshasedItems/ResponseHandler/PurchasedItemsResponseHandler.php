<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBill;
use League\Fractal;

class PurchasedItemsResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $shippingStatus = [];
    private $paymenStatus;

    public function transform(UserBill $data)
    {
        $this->data = $data;
        $this->currency = NULL;
        $this->setDefaultData();
        $this->setBillItems();
        return $this->return;
    }

    private function setDefaultData()
    {
        $country = \OlaHub\UserPortal\Models\Country::where('id', $this->data->country_id)->first();
        $this->currency = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($country->currencyData);
        $payData = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPayUsed($this->data);
        $payStatus = $this->setPayStatusData();
        $this->return = [
            "billNum" => isset($this->data->billing_number) ? $this->data->billing_number : NULL,
            "billPaidBy" => isset($payData) ? $payData : NULL,
            "billCurrency" => $this->currency,
            "billPaidFor" => isset($this->data->pay_for) ? $this->data->pay_for : 0,
            "celebration" => $this->setCelebration(),
            "billIsGift" => isset($this->data->is_gift) ? $this->data->is_gift : 0,
            "billSubtotal" => number_format($this->data->billing_total - $this->data->shipping_fees + $this->data->promo_code_saved, 2, ".", ",") . " " . $this->currency,
            "billShippingFees" => isset($this->data->shipment_details) ? $this->getShipmentDetails($this->data->shipment_details) : NULL,
            "billTotal" => isset($this->data->billing_total) ? number_format($this->data->billing_total, 2, ".", ",") . " " . $this->currency : NULL,
            "billFees" => $this->data->billing_fees ? number_format($this->data->billing_fees, 2, ".", ",") . " " . $this->currency : NULL,
            "billVoucher" => isset($this->data->voucher_used) ? number_format($this->data->voucher_used, 2, ".", ",") . " " . $this->currency : 0,
            "billVoucherAfter" => isset($this->data->voucher_after_pay) ? number_format($this->data->voucher_after_pay, 2, ".", ",") . " " . $this->currency : 0,
            "orderAddress" => isset($this->data->order_address) ? unserialize($this->data->order_address) : [],
            "billCountryName" => $this->getCountry($this->data, $country),
            "billDate" => isset($this->data->billing_date) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDateTime($this->data->billing_date) : NULL,
            "billStatus" => isset($payStatus["name"]) ? $payStatus["name"] : "Fail",
            "billShippingEnabled" => isset($payStatus["shipping"]) ? $payStatus["shipping"] : 0,
        ];
    }

    private function getShipmentDetails($data)
    {
        $data = @unserialize($data);
        if (!$data)
            return NULL;
        $languageArray = explode("_", app('session')->get('def_lang')->default_locale);
        $lang = strtolower($languageArray[0]);
        $return = [];
        foreach ($data as $row) {
            $return[] = array(
                'amount' => $row['amount'] . " " . ($lang == 'en' ? $row['currency']['code'] : $row['currency']['native_code']),
                'country' => ($lang == 'ar' ? $row['country']->ar : $row['country']->en)
            );
        }
        return $return;
    }

    private function getCountry($data, $country)
    {
        if (isset(unserialize($this->data->order_address)['country'])) {
            $data = unserialize($this->data->order_address)['country'];
            $data = \OlaHub\UserPortal\Models\Country::whereRaw("JSON_EXTRACT(name, '$.en') = '" . $data . "'")->first();
        }
        $data = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name');
        return $data;
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

    private function setItemImageData($image)
    {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    private function setPaymentImageData($image)
    {
        if ($image) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setCelebration()
    {
        $celebrationName = NULL;
        $languageArray = explode("_", app('session')->get('def_lang')->default_locale);
        $lang = strtolower($languageArray[0]);
        if ($this->data->pay_for > 0) {
            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::find($this->data->pay_for);
            if ($celebration) {
                $occassion = \OlaHub\UserPortal\Models\Occasion::where('id', $celebration->occassion_id)->first();
                $celebrationName = json_decode($occassion->name)->$lang;
            }
        }
        return $celebrationName;
    }

    private function setItemStatus($userBillDetail)
    {
        $orderStatus = '';
        if (($this->paymenStatus && $this->paymenStatus->shipping_enabled) || ($this->data->pay_status == 0 && $this->data->voucher_used > 0)  && isset($this->shippingStatus[$userBillDetail->id]) && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
            $this->shippingStatus[$userBillDetail->id] = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($userBillDetail->shipping_status);
            if ($this->shippingStatus[$userBillDetail->id]) {
                $orderStatus = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->shippingStatus[$userBillDetail->id], "name");
            }
        }
        return $orderStatus;
    }

    private function setItemCancelStatus($userBillDetail)
    {
        $cancelStatus = 0;
        $policy = $this->getItemPolicy($userBillDetail);
        if ($policy) {
            $exchange_days = $policy->exchange_days;
            $allow_exchange = $policy->allow_exchange;
            if($allow_exchange && $exchange_days > 0) {
                $billDate = $userBillDetail->created_at;
                $allow_date_exchange = date('Y-m-d', strtotime("+" . $exchange_days-1 . " days", strtotime($billDate)));
                if(strtotime($allow_date_exchange) >= strtotime(date('Y-m-d'))){
                    if ((int)$this->data->paid_by == 255) {
                        if ((($this->paymenStatus && $this->paymenStatus->id == 13)) && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
                            $cancelStatus = 1;
                        }
                    }else{
                        if ((($this->paymenStatus && $this->paymenStatus->shipping_enabled)) && isset($this->shippingStatus[$userBillDetail->id]) && $this->shippingStatus[$userBillDetail->id]->cancel_enabled && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
                            $cancelStatus = 1;
                        }
                    }
                }
            }
        }
        return $cancelStatus;
    }

    private function setItemRefundStatus($userBillDetail)
    {
        $refundStatus = 0;
        $policy = $this->getItemPolicy($userBillDetail);
        if ($policy) {
            $refund_days = $policy->refund_days;
            $allow_refund = $policy->allow_refund;
            if($allow_refund && $refund_days > 0) {
                $deliveryDate = $userBillDetail->updated_at;
                $allow_date_refund = date('Y-m-d', strtotime("+" . $refund_days-1 . " days", strtotime($deliveryDate)));
                if(strtotime($allow_date_refund) >= strtotime(date('Y-m-d'))) {
                    if ((int)$this->data->paid_by == 255) {
                        if ((($this->paymenStatus && $this->paymenStatus->shipping_enabled)) && isset($this->shippingStatus[$userBillDetail->id]) && $this->shippingStatus[$userBillDetail->id]->refund_enabled && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
                            $refundStatus = 1;
                        }
                    }else{
                        if ((($this->paymenStatus && $this->paymenStatus->shipping_enabled)) && isset($this->shippingStatus[$userBillDetail->id]) && $this->shippingStatus[$userBillDetail->id]->refund_enabled && !$userBillDetail->is_canceled && !$userBillDetail->is_refund) {
                            $refundStatus = 1;
                        }
                    }
                }
            }
        }
        return $refundStatus;
    }

    private function setBillItems()
    {
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
                'itemPrice' => number_format($userBillDetail->country_paid, 2, ".", ",") . " " . $this->currency,
                'itemImage' => $this->setItemImageData($userBillDetail->item_image),
                'paymentImage' =>$userBillDetail->payment_image!= ""? $this->setPaymentImageData($userBillDetail->payment_image) : 
                $userBillDetail->payment_image,
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

    private function setReviewInfo($review)
    {
        $info = [
            "userRate" => isset($review->rating) ? $review->rating : 0,
            "userReview" => isset($review->review) ? $review->review : '',
        ];

        return $info;
    }

    private function getItemPolicy($userBillDetail)
    {
        $policy = false;
        switch ($userBillDetail->item_type) {
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::where("id", $userBillDetail->item_id)->first();
                break;
            case "designer":
                $item = \OlaHub\UserPortal\Models\DesignerItem::where("id", $userBillDetail->item_id)->first();

                break;
        }
        if($item){
            $policy = $item->exchangePolicy;
        }
        return $policy;
    }
}
