<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Designer;
use League\Fractal;

class DesignerResponseHandler extends Fractal\TransformerAbstract
{
    private $return;
    private $data;

    public function transform(Designer $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $this->return = [
            "desginerId" => isset($this->data->id) ? $this->data->id : 0,
            "desginerSlug" => isset($this->data->designer_slug) ? $this->data->designer_slug : null,
            "desginerBrandName" => isset($this->data->brand_name) ? \OlaHub\UserPortal\Helpers\CommonHelper::returnCurrentLangField($this->data, 'brand_name') : null,
            "desginerLogo" => isset($this->data->logo_ref) ? \OlaHub\UserPortal\Helpers\CommonHelper::setContentUrl($this->data->logo_ref) : null,
        ];
    }
}
