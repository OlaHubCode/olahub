<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model {

    protected $table = 'lkp_payment_method_types';
    static $columnsMaping = [
        'paymentType' => [
            'column' => 'type_id',
            'type' => 'number',
            'manyToMany' => 'typeDataSync',
            'validation' => 'required|numeric'
        ],
    ];

    public function typeData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ManyToMany\PaymentTypeRelation', 'payment_id');
    }

    public function typeDataSync() {
        return $this->belongsTo('OlaHub\UserPortal\Models\PaymentType', 'payment_id');
    }

}
