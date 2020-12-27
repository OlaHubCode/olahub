<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class UserBillDetails extends Model {

    // use SoftDeletes;
    protected $table = 'billing_items';

    function mainBill() {
        return $this->belongsTo('\OlaHub\UserPortal\Models\UserBill', 'billing_id');
    }
    function trackingItem(){
        return $this->hasMany('\OlaHub\UserPortal\Models\UserBillTracking','billing_item_id');
    }

}
