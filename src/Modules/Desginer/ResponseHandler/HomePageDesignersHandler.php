<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Designer;
use League\Fractal;

class HomePageDesignersHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Designer $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "designer" => isset($this->data->id) ? $this->data->id : 0,
            "designerSlug" => isset($this->data->designer_slug) ? $this->data->designer_slug : null,
            "designerName" => isset($this->data->brand_name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, "brand_name") : null,
            "designerImage" => isset($this->data->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->logo_ref) : null,
        ];
    }

}