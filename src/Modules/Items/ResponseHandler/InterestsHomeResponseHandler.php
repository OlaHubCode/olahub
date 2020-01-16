<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Interests;
use League\Fractal;

class InterestsHomeResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Interests $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $itemName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $this->return = [
            "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'interest_slug', $itemName),
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image),
            "itemName" => $itemName,
        ];
    }

}
