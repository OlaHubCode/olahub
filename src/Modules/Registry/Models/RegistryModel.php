<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class RegistryModel extends Model {

    protected $table = 'registries';
    static $columnsMaping = [
         'registryTitle' => [
             'column' => 'title',
             'type' => 'string',
             'relation' => false,
             'validation' => 'required|max:200'
         ],
        'registryImage' => [
            'column' => 'image',
            'type' => 'string',
            'relation' => false,
            'validation' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ],
        'registryVideo' => [
            'column' => 'video',
            'type' => 'string',
            'relation' => false,
            'validation' => 'mimes:mp4,mov,ogg,qt | max:20000'
        ],
        'registryOwner' => [
            'column' => 'user_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:users,id'
        ],
        'registryDate' => [
            'column' => 'registry_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|date_format:Y-m-d|registry_date'
        ],
        'registryOccassion' => [
            'column' => 'occassion_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:occasion_types,id'
        ],
        'registryCountry' => [
            'column' => 'country_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|exists:countries,id'
        ],
        'registryWish' => [
            'column' => 'wish',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:400'
        ],

    ];

    public function cart() {
        return $this->hasOne('OlaHub\UserPortal\Models\Cart', 'registry_id');
    }

    public function registryusers() {
        return $this->hasMany('OlaHub\UserPortal\Models\RegistryUsersModel', 'registry_id');
    }

    public function ownerUser() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }
    public function country() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country', 'country_id');
    }

    static function validateRegistryId($requestData) {
        $validator = \Validator::make($requestData, [
                'registryId' => 'required|numeric|exists:registries,id',
                'registryDate' => ''
        ]);
        if (!$validator->fails()) {
            return true;
        }
        return false;
    }
    public function getUserNameAttribute(){
        if($this->ownerUser){
            $ownerUser = $this->ownerUser;
            $ownerUserName = "$ownerUser->first_name $ownerUser->last_name";
            return $ownerUserName;

        }
    }

}
