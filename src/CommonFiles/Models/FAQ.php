<?php

/**
 * StaticPages model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class FAQ extends Model {

   
    protected $table = 'users_faq';

    public function cateData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\FaqCategory', 'id');
    }

}
