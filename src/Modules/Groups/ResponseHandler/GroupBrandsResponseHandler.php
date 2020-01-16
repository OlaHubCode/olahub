<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Merchant;
use League\Fractal;

class GroupBrandsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(Merchant $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "merchantId" => isset($this->data->id)? $this->data->id: 0,
        ];
    }
    
    

}
