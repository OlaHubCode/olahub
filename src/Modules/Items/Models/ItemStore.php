<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStore extends Model {

    protected $table = 'merchant_stors';

    
    public function merchantRelation(){
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant', 'merchant_id');
    }
    
}
