<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Classification;
use League\Fractal;

class ClassificationForPrequestFormsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Classification $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "value" => isset($this->data->id) ? (string) $this->data->id : 0,
            "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name'),
        ];
    }

}
