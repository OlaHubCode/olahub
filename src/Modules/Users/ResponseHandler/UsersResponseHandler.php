<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class UsersResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(UserModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setUserInterests();
        $this->setShippingAddress();
        return $this->return;
    }

    private function setDefaultData()
    {
        $country = $this->data->country;
        $this->return = [
            "user" => isset($this->data->id) ? $this->data->id : 0,
            "userFullName" => isset($this->data->first_name) ? $this->data->first_name . ' ' . $this->data->last_name : NULL,
            "userFirstName" => isset($this->data->first_name) ? $this->data->first_name : NULL,
            "userLastName" => isset($this->data->last_name) ? $this->data->last_name : NULL,
            "userProfileUrl" => isset($this->data->profile_url	) ? $this->data->profile_url	 : NULL,
            "userPhoneNumber" => isset($this->data->mobile_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber($this->data->mobile_no) : NULL,
            "userEmail" => isset($this->data->email) ? $this->data->email : NULL,
            "userBirthday" => isset($this->data->user_birthday) ? $this->data->user_birthday : NULL,
            "userGender" => isset($this->data->user_gender) ? $this->data->user_gender : NULL,
            "userSocial" => $this->data->facebook_id || $this->data->google_id || $this->data->twitter_id ? 1 : 0,
            "userCountry" => isset($this->data->country_id) ? $this->data->country_id : NULL,
            "userCountryName" => isset($country->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
            "userTwoStep" => $this->data->two_step,
        ];
    }


    private function setDefProfileImageData()
    {
        if (isset($this->data->profile_picture)) {
            $this->return['userProfile'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->profile_picture);
        } else {
            $this->return['userProfile'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }


    private function setShippingAddress()
    {
        $shippingAddress =  $this->data->shippingAddress()->first();
     
        $this->return["shippingAddress"] = isset($shippingAddress->id) ? $shippingAddress->id : 0;
        $this->return["shippingCountry"] = isset($shippingAddress->country_id) ? $shippingAddress->country_id : 0;
        $this->return["userState"] = isset($shippingAddress->shipping_address_state) ? $shippingAddress->shipping_address_state : NULL;
        $this->return["userCity"] = isset($shippingAddress->shipping_address_city) ? $shippingAddress->shipping_address_city : NULL;
        $this->return["userAddressLine1"] = isset($shippingAddress->shipping_address_address_line1) ? $shippingAddress->shipping_address_address_line1 : NULL;
        $this->return["userAddressLine2"] = isset($shippingAddress->shipping_address_address_line2) ? $shippingAddress->shipping_address_address_line2 : NULL;
        $this->return["userZipCode"] = isset($shippingAddress->shipping_address_zip_code) ? $shippingAddress->shipping_address_zip_code : NULL;
        $this->return["userShippingFullName"] = isset($shippingAddress->shipping_address_full_name) ? $shippingAddress->shipping_address_full_name : NULL;
        
    }

    private function setUserInterests()
    {
        $interestsData = [];
        $interestsText = [];
        $uInts = implode("|", explode(",", $this->data->interests));
        $interests = \OlaHub\UserPortal\Models\Interests::withoutGlobalScope('interestsCountry')->whereRaw("CONCAT(',', id, ',') REGEXP ',(".$uInts."),'")->get();
        foreach ($interests as $interest) {
            $interestsData[] = isset($interest->id) ?  $interest->id : 0;
            $interestsText[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, 'name');
        }
        $this->return["userInterests"] = $interestsData;
        $this->return["userInterestsText"] = implode(", ", $interestsText);
    }
}
