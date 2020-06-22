<?php

namespace OlaHub\UserPortal\Models\ManyToMany;

use Illuminate\Database\Eloquent\Model;

class PaymentCountryRelation extends Model {

    protected $table = 'country_payment_methods';
    static $columnsMaping = [
        
    ];

    public function PaymentData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\PaymentMethod', 'payment_method_id');
    }

}
