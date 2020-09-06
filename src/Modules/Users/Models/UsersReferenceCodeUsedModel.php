<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UsersReferenceCodeUsedModel extends Model {
    
    protected $table = 'users_reference_code_used';

    public function user() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }
    public function code() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UsersReferenceCodeModel', 'code_id');
    }

}
