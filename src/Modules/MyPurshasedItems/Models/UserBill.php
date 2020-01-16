<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserBill extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('currntUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
        });
    }

    protected $table = 'billing_history';
    static $columnsMaping = [
        'billType' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|in:1,2,3'
        ],
        'billGate' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric'
        ],
        'billUserID' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,2|numeric'
        ],
        'billCardGift' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => ''
        ],
        'billCelebrationID' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,3|numeric'
        ],
        'billFullName' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,1,2|max:350'
        ],
//        'billCity' => [
//            'column' => 'type_id',
//            'type' => 'number',
//            'relation' => false,
//            'validation' => 'required_if:billType,1,2|max:100'
//        ],
        'billState' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => ''
        ],
        'billPhoneNo' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,1,2|max:30'
        ],
        'billAddress' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,1,2'
        ],
        'billZipCode' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:billType,1,2'
        ],
    ];
    
    function billDetails(){
        return $this->hasMany('\OlaHub\UserPortal\Models\UserBillDetails', 'billing_id');
    }

}
