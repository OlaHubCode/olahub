<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\groups;
use League\Fractal;

class CommunitiesForLandingPageResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(groups $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "itemSlug" => isset($this->data->slug) ? $this->data->slug : 0,
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image, "community"),
            "itemName" => isset($this->data->name) ? $this->data->name : 0,
            "itemDesc" => isset($this->data->description) ? $this->data->description : 0,
        ];
    }

}
