<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CelebrationModel extends Model {

    protected $table = 'celebrations';
    static $columnsMaping = [
        'celebrationTitle' => [
            'column' => 'title',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|max:200'
        ],
        'celebrationOwner' => [
            'column' => 'user_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:users,id'
        ],
        'celebrationDate' => [
            'column' => 'celebration_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'date_format:Y-m-d|olahub_date'
        ],
        'celebrationOccassion' => [
            'column' => 'occassion_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:occasion_types,id'
        ],
        'celebrationCountry' => [
            'column' => 'country_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:countries,id'
        ],
    ];

    public function cart() {
        return $this->hasOne('OlaHub\UserPortal\Models\Cart', 'celebration_id');
    }

    public function shippingAddress() {
        return $this->hasOne('OlaHub\UserPortal\Models\CelebrationShippingAddressModel', 'celebration_id');
    }

    public function celebrationParticipants() {
        return $this->hasMany('OlaHub\UserPortal\Models\CelebrationParticipantsModel', 'celebration_id');
    }

    public function ownerUser() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }

    public function creatorUser() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'created_by');
    }

    static function validateDate($requestData, $celebrationId = false) {
        if ($celebrationId && $celebrationId > 0) {
            $celebration = CelebrationModel::where('id', $celebrationId)->first();
            $maxDate = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($celebration->celebration_date)));
        } else {
            $maxDate = date("Y-m-d H:i:s");
        }
        $dateFormate = date("Y-m-d H:i:s", strtotime("+2 days"));

        $validator = \Validator::make($requestData, [
                    'celebrationDate' => 'required|date_format:Y-m-d|after:' . $dateFormate . 'before:' . $maxDate,
                    'celebrationId' => ''
        ]);
        if (!$validator->fails()) {
            return true;
        }
        return false;
    }

    static function validateCelebrationId($requestData) {
        $validator = \Validator::make($requestData, [
                    'celebrationId' => 'required|numeric|exists:celebrations,id',
                    'celebrationDate' => ''
        ]);
        if (!$validator->fails()) {
            return true;
        }
        return false;
    }

}
