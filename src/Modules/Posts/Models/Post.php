<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Post extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'posts';
    
    protected static function boot() {
        parent::boot();

        static::addGlobalScope('notDeletedPost', function ($query) {
            $query->where('delete', '!=' , 1);
        });
    }

    public function author() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id', 'id');
    }

    public function groupData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\groups', 'group_id');
    }

}
