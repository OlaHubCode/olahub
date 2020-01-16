<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class FriendsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setUserInterests();
        $this->setDefProfileImageData();
        $this->setDefCoverImageData();
        $this->setFriendStatus();
        $this->setFriendsOfFriend();
        return $this->return;
    }

    private function setDefaultData() {
        $country = \OlaHub\UserPortal\Models\Country::where('id',$this->data->country_id)->first();
        $this->return = [
            "profile" => isset($this->data->id) ? $this->data->id : 0,
            "username" => isset($this->data->first_name) ? $this->data->first_name . ' ' .  $this->data->last_name: NULL,
            "profile_url" => isset($this->data->profile_url) ? $this->data->profile_url: NULL,
            "user_birthday" => isset($this->data->user_birthday) ? $this->data->user_birthday : NULL,
            "email" => isset($this->data->email) ? $this->data->email: NULL,
            "mobile_no" => isset($this->data->mobile_no) ? $this->data->mobile_no : NULL,
            "user_gender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            "country" => isset($country) && $country ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
        ];
    }
    
    private function setDefProfileImageData() {
        if (isset($this->data->profile_picture)) {
            $this->return['avatar_url'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['avatar_url'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setDefCoverImageData() {
        if (isset($this->data->cover_photo)) {
            $this->return['cover_photo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo,'COVER_PHOTO');
        } else {
            $this->return['cover_photo'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false,'COVER_PHOTO');
        }
    }
    
    private function setFriendStatus(){
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', (int)$this->data->id)->first();
        $this->return['friendStatus'] = 'new';
        $currentUser = (int)app('session')->get('tempID');
        if($userMongo){
            if(in_array($currentUser, $userMongo->friends)){
                $this->return['friendStatus'] = 'friend';
            }elseif(in_array($currentUser, $userMongo->requests)){
                $this->return['friendStatus'] = 'request';
            }elseif(in_array($currentUser, $userMongo->responses)){
                $this->return['friendStatus'] = 'response';
            }
        }
    }
    
    private function setFriendsOfFriend(){
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $this->data->id)->first();
        $friends = $userMongo->friends;
        $friendsData = [];
        foreach ($friends as $friend){
            $userData = UserModel::where('id',$friend)->first();
			if($userData){
				$country = \OlaHub\UserPortal\Models\Country::where('id',$userData->country_id)->first();
				$friendsData[] = [
					"profile" => isset($userData->id) ? $userData->id : 0,
					"username" => isset($userData->first_name) ? $userData->first_name . ' ' .  $userData->last_name: NULL,
					"profile_url" => isset($userData->profile_url) ? $userData->profile_url: NULL,
					"user_birthday" => isset($userData->user_birthday) ? $userData->user_birthday : NULL,
					"email" => isset($userData->email) ? $userData->email: NULL,
					"mobile_no" => isset($userData->mobile_no) ? $userData->mobile_no : NULL,
					"user_gender" => isset($userData->user_gender) ? $userData->user_gender : NULL,
					"country" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
					"avatar_url" => isset($userData->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
					"cover_photo" => isset($userData->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
				];
			}
        }
        $this->return['friends'] = $friendsData;
    }
    
    
    private function setUserInterests() {
       $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $this->data->id)->first();
       $interestsData = [];
       if(isset($userMongo->intersts) && $userMongo->intersts){
           $interests = \OlaHub\UserPortal\Models\Interests::whereIn('interest_id',$userMongo->intersts)->get();
           foreach ($interests as $interest){
               $interestsData [] = [
                   "value" => isset($interest->interest_id) ?  $interest->interest_id : 0,
                   "text" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, 'name'),
               ];
           }
       }
       $this->return["userInterests"] = $interestsData;
    }

}
