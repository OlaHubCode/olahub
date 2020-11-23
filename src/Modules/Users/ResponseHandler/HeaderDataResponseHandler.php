<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;
use OlaHub\UserPortal\Models\Post;

class HeaderDataResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(UserModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setDefCoverImageData();
        $this->setPoints();
        $this->setUserBalance();
        $this->setLastPrivacy();
        return $this->return;
    }

    private function setDefaultData()
    {
        $userID = $this->data->id;
        //$userBalance = \OlaHub\UserPortal\Models\UserVouchers::where('user_id',$userID)->first();
        $cartItems = \OlaHub\UserPortal\Models\CartItems::whereHas('cartMainData', function ($query) use ($userID) {
            $query->withoutGlobalScope('countryUser')
                ->where('user_id', $userID)
                ->where('country_id', app('session')->get('def_country')->id);
        })->count();
        $notification = \OlaHub\UserPortal\Models\Notifications::where('user_id', $userID)->where('read', 0)->count();
        $this->return = [
            "user" => isset($this->data->id) ? $this->data->id : 0,
            "userFullName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "userFirstName" => isset($this->data->first_name) ? $this->data->first_name : NULL,
            "userLastName" => isset($this->data->last_name) ? $this->data->last_name : NULL,
            "userProfileUrl" => isset($this->data->profile_url) ? $this->data->profile_url : NULL,
            "userGender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            //"balanceNumber" => $userBalance ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userBalance->voucher_balance) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0),
            "cartNumber" => $cartItems > 0 ? $cartItems : 0,
            "notificationCount" => $notification > 0 ? $notification : 0,
            "userCountry" => app('session')->get('def_country')->id,
            "userCountryName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField(app('session')->get('def_country'), 'name'),
            "userFriends" =>  count(\OlaHub\UserPortal\Models\Friends::getFriendsList($this->data->id)),
            "userFollowing" =>  \OlaHub\UserPortal\Models\Following::where('user_id', $this->data->id)->count(),
            "userBalanceNumber" => \OlaHub\UserPortal\Models\UserVouchers::getUserBalance(),
        ];
    }

    private function setDefProfileImageData()
    {
        if (isset($this->data->profile_picture)) {
            $this->return['userProfilePicture'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['userProfilePicture'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPoints()
    {
        $this->return['userPoints'] = 0;
        $points = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as points')->first();
        if ($points->points > 0) {
            $this->return['userPoints'] = $points->points;
        }
    }

    private function setUserBalance()
    {
        $userBalance = 0;
        $points = $this->return['userPoints'];
        $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
        if ($exchangeRate) {
            $points = $points * $exchangeRate->sell_price;
        }
        $userVoucher = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', $this->data->id)->first();
        if ($userVoucher) {
            $userBalance = $userVoucher->voucher_balance;
        }
        $balanceNumber = $userBalance + $points;
        $this->return["balanceNumber"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($balanceNumber);
    }

    private function setDefCoverImageData()
    {
        if (isset($this->data->cover_photo)) {
            $this->return['userCoverPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->cover_photo, 'COVER_PHOTO');
        } else {
            $this->return['userCoverPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false, 'COVER_PHOTO');
        }
    }

    private function setLastPrivacy()
    {
        $this->return['lastPrivacy'] = 1;
        if ($this->data->id) {
            $lastPost = Post::where('user_id', $this->data->id)->whereNull('group_id')->whereNull('friend_id')->orderBy('id', 'desc')->first();
            if ($lastPost) {
                $this->return['lastPrivacy'] = $lastPost->privacy ? $lastPost->privacy : 1;
            }
        }
    }
}
