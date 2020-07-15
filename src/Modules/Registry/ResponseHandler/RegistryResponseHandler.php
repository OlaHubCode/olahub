<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\RegistryModel;
use League\Fractal;

class RegistryResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(RegistryModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setDates();
        return $this->return;
    }

    private function setDefaultData() {
        $participant = \OlaHub\UserPortal\Models\RegistryUsersModel::where('user_id', app('session')->get('tempID'))->where('registry_id',$this->data->id)->first();
        $occassion = \OlaHub\UserPortal\Models\Occasion::where('id',$this->data->occassion_id)->first();
        $owner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id',$this->data->user_id)->first();
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('registry_id',$this->data->id)->first();
        if($cart){
            $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id',$cart->id)->first();
        }
        $this->return = [
            "registry" => isset($this->data->id) ? $this->data->id : 0,
            "registryTitle" =>  isset($this->data->title) ? $this->data->title : NULL,
            "registryStatus" =>  isset($this->data->status) ? $this->data->status : 1,
            "registryWish" =>  isset($this->data->wish) ? $this->data->wish : NULL,
            'registryImage' => isset($this->data->image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            'registryVideo' => isset($this->data->video) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->video) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            "registryDate" => isset($this->data->registry_date) ? $this->data->registry_date : NULL,
            "registryOccassion" => isset($this->data->occassion_id) ? $this->data->occassion_id : 0,
            "registryCountry" => isset($this->data->country_id) ? $this->data->country_id : 0,
            "registryOccassionName" => isset($occassion) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name') : NULL,
            "registryCountryName" => isset($this->data->country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data->country, 'name') : NULL,
            "registryOwner" => isset($this->data->user_id) ? $this->data->user_id : 0,
            "registryOwnerName" => isset($owner) ? $owner->first_name .' '. $owner->last_name : NULL,
            "registryOwnerSlug" => isset($owner) ? $owner->profile_url : NULL,
            "isCreator" => (app('session')->get('tempID') == $this->data->user_id) ? 1 : 0 ,
            "existRegistryGift" => isset($cartItems)? TRUE : FALSE,
        ];
    }
    
    private function setDates() {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["updated"] = isset($this->data->updated_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->updated_at) : NULL;
    }
    
    private function setDefProfileImageData() {
        $owner = $this->data->ownerUser;
        if (isset($owner->profile_picture)) {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($owner->profile_picture);
        } else {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    


}
