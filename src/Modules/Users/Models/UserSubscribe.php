<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscribe extends Model
{

    protected static function boot()
    {
        parent::boot();

        // static::addGlobalScope('voucherCountry', function ($query) {
        //     $query->where('country_id', app('session')->get('def_country')->id);
        // });
    }

    protected $table = 'subscribe';

    
}
