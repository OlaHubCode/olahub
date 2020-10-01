<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Designer;
use League\Fractal;

class DesignerMainResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Designer $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $className = isset($this->data->brand_name) ? \OlaHub\DesignerCorner\commonData\Helpers\CommonHelper::returnCurrentLangField($this->data, 'brand_name') : NULL;
        $follow = \OlaHub\DesignerCorner\Additional\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $this->data->id)
            ->where('type', 2)->first();
        $this->return = [
            "id" => isset($this->data->id) ? $this->data->id : 0,
            "mainSlug" => \OlaHub\DesignerCorner\commonData\Helpers\CommonHelper::checkSlug($this->data, 'designer_slug', $className),
            "mainName" => $className,
            "mainLogo" => \OlaHub\DesignerCorner\commonData\Helpers\CommonHelper::setImageUrl($this->data->logo_ref),
            "mainBanner" => \OlaHub\DesignerCorner\commonData\Helpers\CommonHelper::setImageUrl($this->data->banner_image_ref),
            'followed' => isset($follow) ? true : false,
        ];
    }

}
