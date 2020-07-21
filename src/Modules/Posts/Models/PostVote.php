<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostVote extends Model
{

    protected $table = 'post_vote';

    protected static function boot()
    {
        parent::boot();
    }

    public function post()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Post', 'post_id','post_id');
    }

    public function usersVote()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\VotePostUser', 'vote_id');
    }

    
 
}
