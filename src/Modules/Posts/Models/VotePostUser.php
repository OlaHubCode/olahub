<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class VotePostUser extends Model
{

    protected $table = 'vote_post_user';

    protected static function boot()
    {
        parent::boot();
    }

    public function post_vote()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\PostVote','id');
    }
}


