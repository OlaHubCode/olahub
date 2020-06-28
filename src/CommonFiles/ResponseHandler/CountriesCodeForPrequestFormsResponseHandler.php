<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Country;
use League\Fractal;

class CountriesCodeForPrequestFormsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Country $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "key" => isset($this->data->two_letter_iso_code) ? strtoupper((string) $this->data->two_letter_iso_code) : 'N/A',
            "value" => isset($this->data->two_letter_iso_code) ? strtoupper((string) $this->data->two_letter_iso_code) : 'N/A',
            "flag" => isset($this->data->two_letter_iso_code) ? strtolower((string) $this->data->two_letter_iso_code) : 'N/A',
            "value_int" => isset($this->data->id) ? (int) $this->data->id : 0,
            "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name'),
        ];
    }

}
