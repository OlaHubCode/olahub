<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class UserMongo extends Eloquent {

    protected $connection = 'mongo';
    protected $primaryKey = 'user_id';
    protected $collection = 'users';

    public function posts() {
        return $this->hasMany('OlaHub\UserPortal\Models\Post', 'user_id', 'user_id');
    }

}
