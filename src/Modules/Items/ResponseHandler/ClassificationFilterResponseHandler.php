<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Classification;
use League\Fractal;

class ClassificationFilterResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Classification $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $className = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $this->return = [
            "classID" => isset($this->data->id) ? $this->data->id : 0,
            "classSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'class_slug', $className),
            "className" => $className,
        ];
    }

}
