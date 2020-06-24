<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\Models\AdsMongo;
use League\Fractal;

class AdsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(AdsMongo $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "adToken" => isset($this->data->token)? $this->data->token: NULL,
            "adSlot" => isset($this->data->slot)? $this->data->slot: 0,
            "adRef" => isset($this->data->content_ref)? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->content_ref): NULL,
            "adText" => isset($this->data->content_text)? $this->data->content_text: NULL,
            "adLink" => isset($this->data->access_link)? $this->data->access_link: NULL,
        ];
    }
    
    

}
