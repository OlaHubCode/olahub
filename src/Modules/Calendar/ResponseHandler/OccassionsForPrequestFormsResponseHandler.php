<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\ManyToMany\occasionCountries;
use League\Fractal;

class OccassionsForPrequestFormsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(occasionCountries $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $occassions = $this->data->occasionData;
        $this->return = [
            "value" => isset($occassions->id) ? (string) $occassions->id : 0,
            "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassions, 'name'),
        ];
    }

}
