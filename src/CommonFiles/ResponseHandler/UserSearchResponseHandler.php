<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class UserSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "itemId" => isset($this->data->id) ? $this->data->id : 0,
            "itemName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture),
            "itemSlug" => \OlaHub\UserPortal\Models\UserModel::getUserSlug($this->data),
            "itemType" => 'user'
        ];

    }

}
