<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('countryScope', function(\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->whereHas('countryRelation', function($query) {
                $query->where('country_id', app('session')->get('def_country')->id);
            });
        });
    }

    protected $table = 'payment_methods';
    static $columnsMaping = [
        'paymentType' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required|numeric|in:1,2,3'
        ],
        'celebrationID' => [
            'column' => 'type_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required_if:paymentType,3|numeric'
        ],
    ];

    public function typeDataSync() {
        return $this->belongsToMany('OlaHub\UserPortal\Models\PaymentType', 'paymnet_types', 'payment_id', 'type_id');
    }

    public function countryRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation', 'payment_method_id');
    }

}
