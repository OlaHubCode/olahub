<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class searchUsersForPrequestFormsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "user" => isset($this->data->id) ? $this->data->id : 0,
            "userFullName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "userProfile" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture),
            "userSlug" => \OlaHub\UserPortal\Models\UserModel::getUserSlug($this->data)
        ];
    }

}
