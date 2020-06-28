<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Brand;
use League\Fractal;

class BrandsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Brand $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setMerData();
        return $this->return;
    }

    private function setDefaultData() {
        $brandName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $brandImage = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image_ref);
        $this->return = [
            "brandID" => isset($this->data->id) ? $this->data->id : 0,
            "brandSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'store_slug', $brandName),
            "brandName" => $brandName,
            "brandLogo" => $brandImage,
            "brandPhone" => isset($this->data->contact_phone_no) ? $this->data->contact_phone_no : NULL,
            "brandemail" => isset($this->data->contact_email) ? $this->data->contact_email : NULL,
        ];
    }
    private function setMerData() {
        $merBrand = $this->data->merchant()->first();
        if($merBrand){
            $this->return["brandWebsite"] =  isset($merBrand->company_website) ? $merBrand->company_website : null;
            $this->return["brandAddress"] =  isset($merBrand->company_street_address) ? $merBrand->company_street_address : null;
        }
    }
    

}
