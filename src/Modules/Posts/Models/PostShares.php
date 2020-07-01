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

    public function group()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\groups', 'group_id', 'id');
    }

    public function post()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Post', 'post_id', 'id');
    }

}
