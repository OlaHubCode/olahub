<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostShares extends Model
{

    protected $table = 'posts_shares';

    protected static function boot()
    {
        parent::boot();
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }
}
