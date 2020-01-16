<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBrandMer extends Model {

    protected $table = 'merchant_brands';

    public function brandData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Brand','brand_id');
    }
    
    public function merchantData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant','merchant_id');
    }

}
