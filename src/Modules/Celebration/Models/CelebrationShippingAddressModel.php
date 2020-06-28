<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class CelebrationShippingAddressModel extends Model {

    protected $table = 'celebrations_shipping_address';
    
    static $columnsMaping = [

        'shipping_address_full_name' => [
            'column' => 'shipping_address_full_name',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:300'
        ],
        'shipping_address_city' => [
            'column' => 'shipping_address_city',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:100'
        ],
        'shipping_address_state' => [
            'column' => 'shipping_address_state',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:100'
        ],
        'shipping_address_email' => [
            'column' => 'shipping_address_email',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:100|email'
        ],
        'shipping_address_phone_no' => [
            'column' => 'shipping_address_phone_no',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:50'
        ],
        'shipping_address_address_line1' => [
            'column' => 'shipping_address_address_line1',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'shipping_address_address_line2' => [
            'column' => 'shipping_address_address_line2',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'shipping_address_zip_code' => [
            'column' => 'shipping_address_zip_code',
            'type' => 'number',
            'relation' => false,
            'validation' => 'max:50'
        ],
        
    ];
    
    static function saveShippingAddress($celebrationID, $countryID, $requestData) {
        
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Save shipping address", "action_startData" => $celebrationID. $countryID. json_encode($requestData)]);
        $celebrationShippingAddress = CelebrationShippingAddressModel::where('celebration_id', $celebrationID)->first();
        if (!$celebrationShippingAddress) {
            $celebrationShippingAddress = new CelebrationShippingAddressModel;
            $celebrationShippingAddress->celebration_id = $celebrationID;
            $celebrationShippingAddress->country_id = $countryID;
        }

        if (count($requestData) > 0 && \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationShippingAddressModel::$columnsMaping, (array) $requestData)) {
            foreach ($requestData as $input => $value) {
                if (isset(CelebrationShippingAddressModel::$columnsMaping[$input]['column'])) {
                    $celebrationShippingAddress->{CelebrationShippingAddressModel::$columnsMaping[$input]['column']} = ($value ? $value : null);
                }
            }
            $celebrationShippingAddress->save();
        }
    }
    
    
}
