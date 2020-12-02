<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostComments extends Model
{

    protected $table = 'posts_comments';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('notDeletedComment', function ($query) {
            $query->where('delete', 0);
        });
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }
    
    public function replies()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\PostReplies', 'comment_id');
    }

    public function likes()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CommentLike', 'comment_id');
    }
}
