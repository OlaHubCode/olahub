<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class FriendsResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(UserModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setUserInterests();
        $this->setDefProfileImageData();
        $this->setDefCoverImageData();
        $this->setFriendStatus();
        return $this->return;
    }

    private function setDefaultData()
    {
        $country = \OlaHub\UserPortal\Models\Country::where('id', $this->data->country_id)->first();
        $this->return = [
            "profile" => $this->data->id,
            "username" => $this->data->first_name . ' ' .  $this->data->last_name,
            "profile_url" => $this->data->profile_url,
            "user_birthday" => isset($this->data->user_birthday) ? $this->data->user_birthday : NULL,
            "email" => isset($this->data->email) ? $this->data->email : NULL,
            "mobile_no" => isset($this->data->mobile_no) ? $this->data->mobile_no : NULL,
            "user_gender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            "country" => isset($country) && $country ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
        ];
    }

    private function setDefProfileImageData()
    {
        if (isset($this->data->profile_picture)) {
            $this->return['avatar_url'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['avatar_url'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDefCoverImageData()
    {
        if (isset($this->data->cover_photo)) {
            $this->return['cover_photo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo, 'COVER_PHOTO');
        } else {
            $this->return['cover_photo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'COVER_PHOTO');
        }
    }

    private function setFriendStatus()
    {
        $this->return['friendStatus'] = 'new';
        $currentUser = (int) app('session')->get('tempID');
        $friend = \OlaHub\UserPortal\Models\Friends::getFriend($currentUser, $this->data->id);
        if ($friend) {
            if ($friend->status == 1) {
                $this->return['friendStatus'] = 'friend';
                $this->setFriendsOfFriend();
            } else if($friend->status == 2) {
            if ($friend->user_id == $this->data->id)
                $this->return['friendStatus'] = 'request';
            else if ($friend->friend_id == $this->data->id)
                $this->return['friendStatus'] = 'response';
            } else {
              $this->return = ['friendStatus' => 'block'];
                // $this->return['friendStatus'] = 'block';
            }
        }
    }

    private function setFriendsOfFriend()
    {
        $friends = \OlaHub\UserPortal\Models\Friends::getFriends($this->data->id)->get();
        $friendsData = [];
        foreach ($friends as $friend) {
            $country = \OlaHub\UserPortal\Models\Country::where('id', $friend->country_id)->first();
            $friendsData[] = [
                "profile" => isset($friend->id) ? $friend->id : 0,
                "username" => isset($friend->first_name) ? $friend->first_name . ' ' .  $friend->last_name : NULL,
                "profile_url" => isset($friend->profile_url) ? $friend->profile_url : NULL,
                "user_birthday" => isset($friend->user_birthday) ? $friend->user_birthday : NULL,
                "email" => isset($friend->email) ? $friend->email : NULL,
                "mobile_no" => isset($friend->mobile_no) ? $friend->mobile_no : NULL,
                "user_gender" => isset($friend->user_gender) ? $friend->user_gender : NULL,
                "country" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
                "avatar_url" => isset($friend->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                "cover_photo" => isset($friend->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            ];
        }
        $this->return['friends'] = $friendsData;
    }


    private function setUserInterests()
    {
        $interestsData = [];
        $uInts = implode("|", explode(",", $this->data->interests));
        $interests = \OlaHub\UserPortal\Models\Interests::withoutGlobalScope('interestsCountry')->whereRaw("CONCAT(',', id, ',') REGEXP ',(" . $uInts . "),'")->get();
        foreach ($interests as $interest) {
            $interestsData[] = [
                "value" => isset($interest->id) ?  $interest->id : 0,
                "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, 'name'),
            ];
            $interestsData[] = isset($interest->id) ?  $interest->id : 0;
        }
        $this->return["userInterests"] = $interestsData;
    }
}
