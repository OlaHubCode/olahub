<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistryModel extends Model
{
    use SoftDeletes;
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
        ],
        'registryVideo' => [
            'column' => 'video',
            'type' => 'string',
            'relation' => false,
        ],
        'registryDate' => [
            'column' => 'registry_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|date_format:Y-m-d'
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
        'registryPublish' => [
            'column' => 'is_published',
            'type' => 'number',
            'relation' => false,
        ],

    ];

    public function cart()
    {
        return $this->hasOne('OlaHub\UserPortal\Models\Cart', 'registry_id');
    }

    public function registryusers()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\RegistryUsersModel', 'registry_id');
    }

    public function ownerUser()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }
    public function country()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country', 'country_id');
    }
    public function celebrations()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CelebrationModel', 'registry_id');
    }

    static function validateRegistryId($requestData)
    {
        $validator = \Validator::make($requestData, [
            'registryId' => 'required|numeric|exists:registries,id',
            'registryDate' => ''
        ]);
        if (!$validator->fails()) {
            return true;
        }
        return false;
    }
    public function getUserNameAttribute()
    {
        if ($this->ownerUser) {
            $ownerUser = $this->ownerUser;
            $ownerUserName = "$ownerUser->first_name $ownerUser->last_name";
            return $ownerUserName;
        }
    }
}
