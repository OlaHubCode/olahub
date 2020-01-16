<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserShippingAddressModel extends Model {
    
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        
        static::addGlobalScope('currentUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
            $query->where('country_id', app('session')->get('def_country')->id);
        });
        
        static::saving(function ($model){
            if(!$model->country_id){
                $model->country_id = app('session')->get('def_country')->id;
            }
            if(!$model->user_id){
                $model->user_id = app('session')->get('tempID');
            }
        });
    }

    protected $table = 'user_shipping_addresses';
    static $columnsMaping = [
        'userState' => [
            'column' => 'shipping_address_state',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userCity' => [
            'column' => 'shipping_address_city',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userAddressLine1' => [
            'column' => 'shipping_address_address_line1',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userAddressLine2' => [
            'column' => 'shipping_address_address_line2',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userZipCode' => [
            'column' => 'shipping_address_zip_code',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'userShippingFullName' => [
            'column' => 'shipping_address_full_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
    ];

    public function getColumns($requestData, $userShippingAddress = false) {
        if ($userShippingAddress) {
            $array = $userShippingAddress;
        } else {
            $array = new \stdClass;
        }

        foreach ($requestData as $key => $value) {
            if (isset($this->columnsMaping[$key]['column'])) {
                $array->{$this->columnsMaping[$key]['column']} = $value;
            }
        }
        return $array;
    }

    static function checkUserShippingAddress($userID, $countryID) {
        (new \OlaHub\UserPortal\Helpers\LogHelper())->setActionsData(["action_name" => "Check user shipping address", "action_startData" => $userID. $countryID]);
        $return = [];
        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $userID)->first();
        $address = UserShippingAddressModel::withoutGlobalScope("currentUser")->where('user_id', $userID)
                ->where('country_id', $countryID)
                ->where('is_default', '1')
                ->first();
        if (!$address) {
            $address = new UserShippingAddressModel;
            $address->user_id = $userID;
            $address->country_id = $countryID;
            $address->save();
        }

        $return['addressFullName'] = $address->shipping_address_full_name ? $address->shipping_address_full_name : $user->first_name . " " . $user->last_name;
        $return['addressCountryName'] = null;
        $return['addressCity'] = null;
        $return['addressState'] = null;
        $return['addressEmail'] = null;
        $return['addressPhoneNumber'] = null;
        $return['addressAddress'] = null;
        $return['addressZipCode'] = null;
        return $return;
    }

}
