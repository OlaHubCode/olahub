<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\groups;
use League\Fractal;

class GroupSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(groups $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "itemId" => isset($this->data->{"_id"}) ? $this->data->{"_id"} : 0,
            "itemName" => isset($this->data->name) ? $this->data->name : NULL,
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image),
            "itemCover" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover),
            "itemType" => 'group'
        ];
    }

}
