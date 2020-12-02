<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{

    protected $table = 'comment_like';

    protected static function boot()
    {
        parent::boot();
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }
}