<?php

namespace OlaHub\UserPortal\Models\ManyToMany;

use Illuminate\Database\Eloquent\Model;

class PaymentTypeRelation extends Model {

    protected $table = 'paymnet_types';
    static $columnsMaping = [
        
    ];

    public function PaymentData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ManyToMany\PaymentTypeRelation', 'payment_id');
    }

}
