<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserBillDetails extends Model {

    protected $table = 'billing_items';

    function mainBill() {
        return $this->belongsTo('\OlaHub\UserPortal\Models\UserBill', 'billing_id');
    }

}
