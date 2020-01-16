<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Occasion;
use League\Fractal;

class OccasionsHomeResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Occasion $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "itemName" => isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL,
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->logo_ref),
            "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data,'occasion_slug', $this->data->name)
        ];
    }

}
