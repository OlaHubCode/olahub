<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Country;
use League\Fractal;

class CountriesForPrequestFormsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Country $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "value" => isset($this->data->id) ? (string) $this->data->id : 0,
            "flag" => isset($this->data->two_letter_iso_code) ? strtolower($this->data->two_letter_iso_code) : 0,
            "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name'),
        ];
    }

}
