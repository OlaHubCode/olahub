<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostReplies extends Model
{

    protected $table = 'posts_replies';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('notDeletedReply', function ($query) {
            $query->where('delete', 0);
        });
    }

    public function author()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }
}
