<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CelebrationModel;
use League\Fractal;

class CelebrationResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CelebrationModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setCelebrationShippingAddress();
        $this->setDates();
        return $this->return;
    }

    private function setDefaultData() {
        $country = \OlaHub\UserPortal\Models\Country::where('id',$this->data->country_id)->first();
        $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->where('celebration_id',$this->data->id)->first();
        $occassion = \OlaHub\UserPortal\Models\Occasion::where('id',$this->data->occassion_id)->first();
        $owner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id',$this->data->user_id)->first();
        $creator = $this->data->creatorUser;
        $paiedParticipant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id',$this->data->id)->where('payment_status',3)->first();
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id',$this->data->id)->first();
        $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id',$cart->id)->first();
        $this->return = [
            "celebration" => isset($this->data->id) ? $this->data->id : 0,
            "celebrationTitle" => isset($this->data->title) ? $this->data->title : NULL,
            "celebrationDate" => isset($this->data->celebration_date) ? $this->data->celebration_date : NULL,
            "celebrationOriginalDate" => isset($this->data->original_celebration_date) ? $this->data->original_celebration_date : NULL,
            "celebrationCommitDate" => isset($this->data->commit_date) ? $this->data->commit_date : NULL,
            "celebrationOccassion" => isset($this->data->occassion_id) ? $this->data->occassion_id : 0,
            "celebrationOccassionName" => isset($occassion) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name') : NULL,
            "celebrationCountry" => isset($this->data->country_id) ? (string)$this->data->country_id : 0,
            "celebrationCountryCode" => isset($country) ? $country->two_letter_iso_code : NULL,
            "celebrationCountryName" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
            "celebrationOwner" => isset($this->data->user_id) ? $this->data->user_id : 0,
            "celebrationOwnerName" => isset($owner) ? $owner->first_name .' '. $owner->last_name : NULL,
            "celebrationOwnerSlug" => isset($owner) ? $owner->profile_url : NULL,
            "celebrationCreatorName" => isset($creator) ? $creator->first_name .' '. $creator->last_name : NULL,
            "isCreator" => isset($participant) ? $participant->is_creator : 0 ,
            "isApprove" => isset($participant) ? $participant->is_approved : 0,
            "isPaied" => isset($participant) ? $participant->payment_status : 0,
            "celebrationOwnerName" => isset($owner) ? $owner->first_name .' '. $owner->last_name : NULL,
            "celebrationCreatorName" => isset($creator) ? $creator->first_name .' '. $creator->last_name : NULL,
            "celebrationStatus" => $this->data->celebration_status,
            "existCelebrationParticipantPaied" => isset($paiedParticipant)? TRUE : FALSE,
            "existCelebrationGift" => isset($cartItems)? TRUE : FALSE,
            "hiddenScheduleBtn" => isset($this->data->original_celebration_date) && ($this->data->original_celebration_date == date("Y-m-d", strtotime("+3 days")) || $this->data->original_celebration_date == date("Y-m-d", strtotime("+2 days")) || $this->data->original_celebration_date == date("Y-m-d", strtotime("+1 days")) || $this->data->original_celebration_date < date("Y-m-d")) ? TRUE : FALSE
        ];
    }
    
    private function setDates() {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["creator"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
        $this->return["updated"] = isset($this->data->updated_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->updated_at) : NULL;
        $this->return["updater"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::defineRowCreator($this->data);
    }
    
    private function setDefProfileImageData() {
        $owner = $this->data->ownerUser;
        if (isset($owner->profile_picture)) {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($owner->profile_picture);
        } else {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setCelebrationShippingAddress() {
        $shippingData = \OlaHub\UserPortal\Models\CelebrationShippingAddressModel::where('celebration_id',$this->data->id)->first();
        $this->return["shipping_address_address_line1"] = isset($shippingData->shipping_address_address_line1) ? $shippingData->shipping_address_address_line1 : NULL;
        $this->return["shipping_address_zip_code"] = isset($shippingData->shipping_address_zip_code) ? $shippingData->shipping_address_zip_code : NULL;
        $this->return["shipping_address_city"] = isset($shippingData->shipping_address_city) ? $shippingData->shipping_address_city : NULL;
        $this->return["shipping_address_state"] = isset($shippingData->shipping_address_state) ? $shippingData->shipping_address_state : NULL;
        $this->return["shipping_address_phone_no"] = isset($shippingData->shipping_address_phone_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber($shippingData->shipping_address_phone_no) : NULL;
    }
    


}
