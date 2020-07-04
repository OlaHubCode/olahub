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
        return $this->belongsTo('OlaHub\UserPortal\Models\Post', 'post_id', 'post_id');
    }

    public function getUserNameAttribute(){
            if($this->author){
                $author = $this->author;
                $authorName = "$author->first_name $author->last_name";
                return $authorName;

        }
    }

}
