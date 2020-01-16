<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\groups;
use League\Fractal;

class MainGroupResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(groups $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefGroupImageData();
        $this->setGroupOwner();
        $this->setDefCoverImageData();
        $this->setInterestsData();
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "groupId" => isset($this->data->_id) ? $this->data->_id : 0,
            "groupName" => isset($this->data->name) ? $this->data->name : NULL,
            "groupDescription" => isset($this->data->group_desc) ? $this->data->group_desc : NULL,
            "groupPrivacy" => isset($this->data->privacy) ? $this->data->privacy : 0,
            "groupPostApprove" => isset($this->data->posts_approve) ? $this->data->posts_approve : 0,
            "onlyMyStores" => isset($this->data->onlyMyStores) ? $this->data->onlyMyStores : FALSE,
            "groupMembersNumbers" => count($this->data->members),
            "isGroupCreator" => $this->data->creator == app('session')->get('tempID') ? TRUE : FALSE,
            "isGroupMember" => in_array(app('session')->get('tempID'), $this->data->members) ? TRUE : FALSE,
            "isGroupRequest" => isset($this->data->requests) && in_array(app('session')->get('tempID'), $this->data->requests) ? TRUE : FALSE,
            "isGroupResponse" => isset($this->data->responses) && in_array(app('session')->get('tempID'), $this->data->responses) ? TRUE : FALSE,
        ];
    }

    private function setDefGroupImageData() {
        if (isset($this->data->image)) {
            $this->return['groupImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image);
        } else {
            $this->return['groupImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setGroupOwner() {
        $this->return["groupOwner"] = 0;
        $this->return["groupOwnerName"] = "";
        if (isset($this->data->creator) && $this->data->creator > 0) {
            $this->return["groupOwner"] = $this->data->creator;
            $owner = \OlaHub\UserPortal\Models\UserModel::find($this->data->creator);
            if($owner){
                $this->return["groupOwnerName"] = "$owner->first_name $owner->last_name";
            }
            
        }
    }

    private function setDefCoverImageData() {
        if (isset($this->data->cover)) {
            $this->return['groupCover'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover);
        } else {
            $this->return['groupCover'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setInterestsData() {
        $interests = \OlaHub\UserPortal\Models\Interests::whereIn('interest_id', $this->data->interests)->get();
        $interestData = [];
        foreach ($interests as $interest) {
            $interestData[] = [
                "id" => isset($interest->interest_id) ? $interest->interest_id : 0,
                "name" => isset($interest->name) ? $interest->name : NULL
            ];
        }
        $this->return['interests'] = $interestData;
    }

}
