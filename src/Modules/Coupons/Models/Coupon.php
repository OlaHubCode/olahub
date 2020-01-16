<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('couponCountry', function ($query) {
            $query->where('country_id', app('session')->get('def_country')->id);
        });
    }

    protected $table = 'promo_codes';

}
