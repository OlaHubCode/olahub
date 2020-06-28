<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Designer;
use League\Fractal;

class DesignersSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Designer $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $brandName = isset($this->data->brand_name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'brand_name') : NULL;
        $brandImage = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->logo_ref);

        $this->return = [
            "itemName" => $brandName,
            "itemImage" => $brandImage,
            "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'designer_slug', $brandName),
            "itemPhone" => isset($this->data->contact_phone_no) ? $this->data->contact_phone_no : NULL,
            "itememail" => isset($this->data->contact_email) ? $this->data->contact_email : NULL,
            "itemWebsite" => isset($this->data->website) ? $this->data->website : null,
            "itemAddress" => isset($this->data->full_address) ? $this->data->full_address : null,
            "itemType" => 'designers'
        ];
    }

}
