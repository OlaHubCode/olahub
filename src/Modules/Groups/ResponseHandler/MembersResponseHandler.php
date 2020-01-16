<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class MembersResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefImageData();
        $this->setDefCoverData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "userId" => isset($this->data->id)? $this->data->id: 0,
            "groupMemberName" => isset($this->data->first_name)? $this->data->first_name . " " . $this->data->last_name : NULL,
            "groupMemberGender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            "groupMemberSlug" => isset($this->data->profile_url) ? $this->data->profile_url: NULL,
        ];
    }
    
    private function setDefImageData() {
        if (isset($this->data->profile_picture)) {
            $this->return['groupMemberImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['groupMemberImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setDefCoverData() {
        if (isset($this->data->cover_photo)) {
            $this->return['groupMemberCoverImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo);
        } else {
            $this->return['groupMemberCoverImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    

}
