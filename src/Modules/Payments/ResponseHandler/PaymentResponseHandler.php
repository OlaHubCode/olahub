<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation;
use League\Fractal;

class PaymentResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $countryData;

    public function transform(PaymentCountryRelation $data) {
        $this->countryData = $data;
        $this->data = $data->PaymentData;
        
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {

        $this->return = [
            "paymentID" => isset($this->data->id) ? $this->data->id : 0,
            "paymentName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name'),
            "paymentLogo" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->logo),
            "paymentExtraFees" => isset($this->countryData->extra_fees) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->countryData->extra_fees) : 0,
            "paymentExtraFeesInt" => isset($this->countryData->extra_fees) ? $this->countryData->extra_fees : 0,
            "paymentForm" => isset($this->data->is_form) && $this->data->is_form ? $this->data->is_form : 0,
        ];
    }

}
