<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class HeaderDataResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setDefCoverImageData();
        $this->setPoints();
        $this->setUserBalance();
        return $this->return;
    }

    private function setDefaultData() {
        $userID = $this->data->id;
        //$userBalance = \OlaHub\UserPortal\Models\UserVouchers::where('user_id',$userID)->first();
        $cartItems = \OlaHub\UserPortal\Models\CartItems::whereHas('cartMainData', function ($query) use($userID){
            $query->withoutGlobalScope('countryUser')
                    ->where('user_id', $userID)
                    ->where('country_id', app('session')->get('def_country')->id);
        })->count();
        $notification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user',$userID)->where('read',0)->count();
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $this->data->id)->first();
        $followed_brands = $userMongo && isset($userMongo->followed_brands) && count($userMongo->followed_brands) > 0 ? count($userMongo->followed_brands) : 0;
        $followed_occassions = $userMongo && isset($userMongo->followed_occassions) && count($userMongo->followed_occassions) > 0 ? count($userMongo->followed_occassions) : 0;
        $followed_designers = $userMongo && isset($userMongo->followed_designers) && count($userMongo->followed_designers) > 0 ? count($userMongo->followed_designers) : 0;
        $followed_interests = $userMongo && isset($userMongo->followed_interests) && count($userMongo->followed_interests) > 0 ? count($userMongo->followed_interests) : 0;
        $this->return = [
            "user" => isset($this->data->id) ? $this->data->id : 0,
            "userFullName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "userFirstName" => isset($this->data->first_name) ? $this->data->first_name : NULL,
            "userLastName" => isset($this->data->last_name) ? $this->data->last_name : NULL,
            "userProfileUrl" => isset($this->data->profile_url) ? $this->data->profile_url: NULL,
            "userGender" => isset($this->data->user_gender) ? $this->data->user_gender: NULL,
            //"balanceNumber" => $userBalance ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userBalance->voucher_balance) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0),
            "cartNumber" => $cartItems > 0 ? $cartItems : 0,
            "notificationCount" =>$notification > 0 ?$notification:0,
            "userCountry" => app('session')->get('def_country')->id,
            "userCountryName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField(app('session')->get('def_country'), 'name'),
            "userFriends" => $userMongo && isset($userMongo->friends) && count($userMongo->friends) > 0 ? count($userMongo->friends) : 0,
            "userFollowing" => $followed_brands + $followed_occassions + $followed_designers + $followed_interests,
            "userBalanceNumber" => \OlaHub\UserPortal\Models\UserVouchers::getUserBalance(),
        ];
    }
    
    private function setDefProfileImageData() {
        if (isset($this->data->profile_picture)) {
            $this->return['userProfilePicture'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['userProfilePicture'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setPoints() {
        $this->return['userPoints'] = 0;
        $points = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as points')->first();
        if($points->points > 0){
            $this->return['userPoints'] = $points->points;
        }
    }
    
    private function setUserBalance() {
        $userBalance = 0;
        $points = $this->return['userPoints'];
        $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
        if ($exchangeRate) {
            $points = $points * $exchangeRate->sell_price;
        }
        $userVoucher = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', $this->data->id)->first();
        if($userVoucher){
            $userBalance = $userVoucher->voucher_balance;
        }
        $balanceNumber = $userBalance + $points;
        $this->return["balanceNumber"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($balanceNumber);
    }
    
    private function setDefCoverImageData() {
        if (isset($this->data->cover_photo)) {
            $this->return['userCoverPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo,'COVER_PHOTO');
        } else {
            $this->return['userCoverPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false,'COVER_PHOTO');
        }
    }
    

}
