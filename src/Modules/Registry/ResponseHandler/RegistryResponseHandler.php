<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\RegistryModel;
use League\Fractal;

class RegistryResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(RegistryModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setDefProfileImageData();
        $this->setDates();
        $this->checkParticipant();
        return $this->return;
    }

    private function setDefaultData()
    {
        $participant = \OlaHub\UserPortal\Models\RegistryUsersModel::where('user_id', app('session')->get('tempID'))->where('registry_id', $this->data->id)->first();
        $occassion = \OlaHub\UserPortal\Models\Occasion::where('id', $this->data->occassion_id)->first();
        $owner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->data->user_id)->first();
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('registry_id', $this->data->id)->first();
        if ($cart) {
            $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->first();
        }
        $this->return = [
            "registryId" => isset($this->data->id) ? $this->data->id : 0,
            "registryTitle" =>  isset($this->data->title) ? $this->data->title : NULL,
            "registryStatus" =>  isset($this->data->status) ? $this->data->status : 1,
            "registryWish" =>  isset($this->data->wish) ? $this->data->wish : NULL,
            'registryImage' => isset($this->data->image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->image) : NULL,
            'registryVideo' => isset($this->data->video) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($this->data->video) : NULL,
            "registryDate" => isset($this->data->registry_date) ? $this->data->registry_date : NULL,
            "registryOccassion" => isset($this->data->occassion_id) ? $this->data->occassion_id : 0,
            "registryCountry" => isset($this->data->country_id) ? $this->data->country_id : 0,
            "registryOccassionName" => isset($occassion) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, 'name') : NULL,
            "registryCountryName" => isset($this->data->country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data->country, 'name') : NULL,
            "registryCountryCode" => isset($this->data->country) ? $this->data->country->two_letter_iso_code : NULL,
            "registryOwner" => isset($this->data->user_id) ? $this->data->user_id : 0,
            "registryOwnerName" => isset($owner) ? $owner->first_name . ' ' . $owner->last_name : NULL,
            "registryOwnerSlug" => isset($owner) ? $owner->profile_url : NULL,
            "isCreator" => (app('session')->get('tempID') == $this->data->user_id) ? 1 : 0,
            "existRegistryGift" => isset($cartItems) ? TRUE : FALSE,
            "isPublished" => isset($this->data->is_published) ? $this->data->is_published : 0,
        ];
    }

    private function setDates()
    {
        $this->return["created"] = isset($this->data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->created_at) : NULL;
        $this->return["updated"] = isset($this->data->updated_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->data->updated_at) : NULL;
    }

    private function checkParticipant()
    {
        if (app('session')->get('tempID') != $this->data->user_id) {
            $participant = \OlaHub\UserPortal\Models\RegistryUsersModel::where('registry_id', $this->data->id)->where('user_id', app('session')->get('tempID'))->first();
            $this->return["existInRegistry"] = isset($participant) ? true : false;
            $this->setOwnerShippingAddressData();
        }
    }

    private function setDefProfileImageData()
    {
        $owner = $this->data->ownerUser;
        if (isset($owner->profile_picture)) {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($owner->profile_picture);
        } else {
            $this->return['ownerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    private function setOwnerShippingAddressData()
    {
        $shippingAddress = \OlaHub\UserPortal\Models\UserShippingAddressModel::withoutGlobalScope('currentUser')->where('user_id', $this->data->user_id)->first();
        $this->return['shipping_address']["shipping_address_full_name"] = isset($shippingAddress->shipping_address_full_name) ? $shippingAddress->shipping_address_full_name : NULL;
        $this->return['shipping_address']["shipping_address_city"] = isset($shippingAddress->shipping_address_city) ? $shippingAddress->shipping_address_city : NULL;
        $this->return['shipping_address']["shipping_address_state"] = isset($shippingAddress->shipping_address_state) ? $shippingAddress->shipping_address_state : NULL;
        $this->return['shipping_address']["shipping_address_email"] = isset($shippingAddress->shipping_address_email) ? $shippingAddress->shipping_address_email : NULL;
        $this->return['shipping_address']["shipping_address_phone_no"] = isset($shippingAddress->shipping_address_phone_no) ? $shippingAddress->shipping_address_phone_no : NULL;
        $this->return['shipping_address']["shipping_address_address_line1"] = isset($shippingAddress->shipping_address_address_line1) ? $shippingAddress->shipping_address_address_line1 : NULL;
        $this->return['shipping_address']["shipping_address_address_line2"] = isset($shippingAddress->shipping_address_address_line2) ? $shippingAddress->shipping_address_address_line2 : NULL;
        $this->return['shipping_address']["shipping_address_zip_code"] = isset($shippingAddress->shipping_address_zip_code) ? $shippingAddress->shipping_address_zip_code : NULL;
        $this->return['shipping_address']["celebrationCountry"] = isset($shippingAddress->country_id) ? (string) $shippingAddress->country_id : NULL;
    }
}
