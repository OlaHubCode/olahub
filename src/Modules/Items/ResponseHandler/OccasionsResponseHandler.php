<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Occasion;
use League\Fractal;

class OccasionsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Occasion $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $brandName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $this->return = [
            "occasionID" => isset($this->data->id) ? $this->data->id : 0,
            "occasionSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'occasion_slug', $brandName),
            "occasionName" => $brandName,
        ];
    }

}
