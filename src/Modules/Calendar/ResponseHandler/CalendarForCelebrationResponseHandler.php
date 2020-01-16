<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CalendarModel;
use League\Fractal;

class CalendarForCelebrationResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CalendarModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setCelebrationShippingAddressData();
        return $this->return;
    }

    private function setDefaultData() {
        $occassion = $this->data->occasion;
        $this->return = [
            "calendarId" => isset($this->data->id) ? $this->data->id : 0,
            "celebrationDate" => isset($this->data->calender_date) ? $this->data->calender_date : NULL,
            "celebrationOccassion" => isset($this->data->occasion_id) ? $this->data->occasion_id : NULL,
            "celebrationOccassionName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name'),
            "celebrationTitle" => isset($this->data->title) ? $this->data->title : NULL,
            "celebrationOwner" => isset($this->data->user_id) ? $this->data->user_id : NULL,
            
        ];
    }
    
    private function setDefProfileImageData() {
        $user = \OlaHub\UserPortal\Models\UserModel::where('id',$this->data->user_id)->first();
        $this->return["celebrationOwnerName"] = isset($user) ? $user->first_name . ' ' . $user->last_name : NULL;
        if (isset($user->profile_picture)) {
            $this->return['celebrationOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture);
            $this->return['celebrationOwnerSlug'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($user,"profile_url", $user->first_name." ".$user->last_name, ".");
        } else {
            $this->return['celebrationOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setCelebrationShippingAddressData() {
        $shippingAddress = \OlaHub\UserPortal\Models\UserShippingAddressModel::withoutGlobalScope('currentUser')->where('user_id',$this->data->user_id)->first();
        $this->return["shipping_address_full_name"] = isset($shippingAddress->shipping_address_full_name) ? $shippingAddress->shipping_address_full_name : NULL;
        $this->return["shipping_address_city"] = isset($shippingAddress->shipping_address_city) ? $shippingAddress->shipping_address_city : NULL;
        $this->return["shipping_address_state"] = isset($shippingAddress->shipping_address_state) ? $shippingAddress->shipping_address_state : NULL;
        if($shippingAddress->shipping_address_email){
            $this->return["shipping_address_email"] = isset($shippingAddress->shipping_address_email) ? $shippingAddress->shipping_address_email : NULL;
        }
        $this->return["shipping_address_phone_no"] = isset($shippingAddress->shipping_address_phone_no) ? $shippingAddress->shipping_address_phone_no : NULL;
        $this->return["shipping_address_address_line1"] = isset($shippingAddress->shipping_address_address_line1) ? $shippingAddress->shipping_address_address_line1 : NULL;
        $this->return["shipping_address_address_line2"] = isset($shippingAddress->shipping_address_address_line2) ? $shippingAddress->shipping_address_address_line2 : NULL;
        $this->return["shipping_address_zip_code"] = isset($shippingAddress->shipping_address_zip_code) ? $shippingAddress->shipping_address_zip_code : NULL;
        $this->return["celebrationCountry"] = isset($shippingAddress->country_id) ? $shippingAddress->country_id : NULL;
        
    }

}
