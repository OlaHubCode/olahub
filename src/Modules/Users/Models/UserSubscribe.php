<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserSubscribe extends Model
{

    protected static function boot()
    {
        parent::boot();
    }

    protected $table = 'subscribe';
}
