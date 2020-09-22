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

class FaqCategory extends Model {

   
    protected $table = 'faq_category';

    public function faq() {
        return $this->hasMany('OlaHub\UserPortal\Models\FAQ', 'category_id');
    }

}
