<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserMongo;
use League\Fractal;

class MyFriendsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserMongo $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        if(isset($this->data->country_id)){
            $country = \OlaHub\UserPortal\Models\Country::where('id',$this->data->country_id)->first();
        }
        $this->return = [
                "profile" => isset($this->data->user_id) ? $this->data->user_id : 0,
                "username" => isset($this->data->username) ? $this->data->username : NULL,
                "profile_url" => isset($this->data->profile_url) ? $this->data->profile_url: NULL,
                "user_gender" => isset($this->data->gender) ? $this->data->gender : NULL,
                "country" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
                "avatar_url" => isset($this->data->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->avatar_url),
                "cover_photo" => isset($this->data->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo),
        ];
    }
    

}
