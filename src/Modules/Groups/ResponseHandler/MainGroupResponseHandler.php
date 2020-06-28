<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\groups;
use League\Fractal;

class MainGroupResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(groups $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->memberStatus();
        $this->setDefGroupImageData();
        $this->setGroupOwner();
        $this->setDefCoverImageData();
        $this->setInterestsData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $this->return = [
            "groupId" => isset($this->data->id) ? $this->data->id : 0,
            "groupName" => isset($this->data->name) ? $this->data->name : NULL,
            "groupDescription" => isset($this->data->description) ? $this->data->description : NULL,
            "groupPrivacy" => isset($this->data->privacy) ? $this->data->privacy : 0,
            "groupPostApprove" => isset($this->data->posts_approve) ? $this->data->posts_approve : 0,
            "onlyMyStores" => isset($this->data->only_my_stores) ? $this->data->only_my_stores : FALSE,
            "groupMembersNumbers" => count($this->data->members),
            "isGroupCreator" => $this->data->creator == app('session')->get('tempID') ? TRUE : FALSE,
            "isGroupMember" => FALSE,
            "isGroupRequest" => FALSE,
            "isGroupResponse" => FALSE,
        ];
    }
    private function memberStatus()
    {
        // 1=member, 2=response, 3=request
        $member = \OlaHub\UserPortal\Models\GroupMembers::where('group_id', $this->data->id)->where('user_id', app('session')->get('tempID'))->first();
        if (isset($member)) {
            $this->return["isGroupMember"] = $member->status == 1 ? TRUE : FALSE;
            $this->return["isGroupResponse"] = $member->status == 2 ? TRUE : FALSE;
            $this->return["isGroupRequest"] = $member->status == 3 ? TRUE : FALSE;
        }
    }
    private function setDefGroupImageData()
    {
        if (isset($this->data->image)) {
            $this->return['groupImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image);
        } else {
            $this->return['groupImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setGroupOwner()
    {
        $this->return["groupOwner"] = 0;
        $this->return["groupOwnerName"] = "";
        if (isset($this->data->creator) && $this->data->creator > 0) {
            $this->return["groupOwner"] = $this->data->creator;
            $owner = \OlaHub\UserPortal\Models\UserModel::find($this->data->creator);
            if ($owner) {
                $this->return["groupOwnerName"] = "$owner->first_name $owner->last_name";
            }
        }
    }

    private function setDefCoverImageData()
    {
        if (isset($this->data->cover)) {
            $this->return['groupCover'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover);
        } else {
            $this->return['groupCover'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setInterestsData()
    {
        $gInts = implode("|", explode(",", $this->data->interests));
        $interests = \OlaHub\UserPortal\Models\Interests::withoutGlobalScope('interestsCountry')->whereRaw("CONCAT(',', id, ',') REGEXP ',(" . $gInts . "),'")->get();
        $interestData = [];
        foreach ($interests as $interest) {
            $interestData[] = [
                "id" => isset($interest->id) ? $interest->id : 0,
                "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, 'name'),
                // "name" => isset($interest->name) ? $interest->name : NULL
            ];
        }
        $this->return['interests'] = $interestData;
    }
}
