<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UsersReferenceCodeModel extends Model {
    
    protected $table = 'users_reference_code';
    static $columnsMaping = [
        'code' => [
            'column' => 'code',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200|required'
        ],
        'type' => [
            'column' => 'type',
            'type' => 'string',
            'relation' => false,
            'validation' => 'max:200'
        ],
        'startDate' => [
            'column' => 'start_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|date_format:Y-m-d'
        ],
        'endDate' => [
            'column' => 'end_date',
            'type' => 'string',
            'relation' => false,
            'validation' => 'required|date_format:Y-m-d'
        ],
        
    ];

    public function user() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }

}
