<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
 use Illuminate\Database\Eloquent\SoftDeletes;

class UserBillTracking extends Model {

     use SoftDeletes;
    protected $table = 'billing_items_tracking';

  
    function trackingStatus()
    {
        return $this->belongsTo('\OlaHub\UserPortal\Models\PaymentShippingStatus', 'shipping_status');
    }

}
