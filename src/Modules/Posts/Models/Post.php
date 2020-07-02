<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{

    protected $table = 'posts';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('notDeletedPost', function ($query) {
            $query->where('delete', '!=', 1);
        });
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }

    public function groupData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\groups', 'group_id');
    }

    public function comments()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\PostComments', 'post_id', 'post_id');
    }
    public function shares()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\PostShares', 'post_id', 'post_id');
    }

    public function likes()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\PostLikes', 'post_id', 'post_id');
    }
}
