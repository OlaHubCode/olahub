<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsers extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('couponUser', function ($query) {
            $query->where('user_id', app('session')->get('tempID'));
        });
    }

    protected $table = 'promo_codes_users';

}
