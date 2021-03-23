<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PostMedia extends Model
{

    protected $table = 'posts_media';
    protected $fillable = [
        'post_id','path','type'
    ];
}
