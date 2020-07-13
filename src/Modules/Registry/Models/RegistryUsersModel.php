<?php

namespace OlaHub\UserPortal\Models;
use Illuminate\Database\Eloquent\Model;

class RegistryUsersModel extends Model {

    protected $table = 'registries_users';
    
    static $columnsMaping = [
        
        'userId' => [
            'column' => 'user_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|exists:users,id'
        ],
        'registryId' => [
            'column' => 'registry_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|exists:registries,id'
        ],
        
        
    ];
    
    public function registry(){
        return $this->belongsTo('OlaHub\UserPortal\Models\RegistryModel','registry_id');
    }

    static function validateMultiUserData($requestData) {
        $data = [];
        $status = TRUE;

        $validator = \Validator::make($requestData, [
            'registryId' => 'required|exists:registries,id',
            'usersId'    => 'required|array',
            'usersId.*'  => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            $status = FALSE;
            $data = $validator->errors()->toArray();
        }
        return ['status' => $status, 'data' => $data];
    }
    
}
