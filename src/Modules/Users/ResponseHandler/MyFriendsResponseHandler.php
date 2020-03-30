<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class MyFriendsResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(UserModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        if (isset($this->data->country_id)) {
            $country = \OlaHub\UserPortal\Models\Country::where('id', $this->data->country_id)->first();
        }
        $this->return = [
            "profile" => $this->data->id,
            "username" => $this->data->first_name . " " . $this->data->last_name,
            "profile_url" => isset($this->data->profile_url) ? $this->data->profile_url : NULL,
            "user_gender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            "country" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
            "avatar_url" => isset($this->data->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture),
            "cover_photo" => isset($this->data->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo),
        ];
    }
}
