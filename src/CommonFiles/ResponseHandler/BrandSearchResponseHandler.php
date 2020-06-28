<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Brand;
use League\Fractal;

class BrandSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Brand $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
//        dd($this->data);
        $brandName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $brandImage = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image_ref);
        $merBrand = $this->data->merchant()->first();
        $this->return = [
            "itemName" => $brandName,
            "itemImage" => $brandImage,
            "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'store_slug', $brandName),
            "itemPhone" => isset($this->data->contact_phone_no) ? $this->data->contact_phone_no : NULL,
            "itememail" => isset($this->data->contact_email) ? $this->data->contact_email : NULL,
            "itemWebsite" => isset($merBrand->company_website) ? $merBrand->company_website : null,
            "itemAddress" => isset($merBrand->company_street_address) ? $merBrand->company_street_address : null,
            "itemType" => 'brand'
        ];
    }

}
