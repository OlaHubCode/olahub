<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostLikes extends Model
{

    protected $table = 'posts_likes';

    protected static function boot()
    {
        parent::boot();
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }
}
