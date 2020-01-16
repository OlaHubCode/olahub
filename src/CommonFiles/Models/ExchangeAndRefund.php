<?php

namespace OlaHub\UserPortal\Models;

class ExchangeAndRefund extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    

    protected $table = 'exchange_refund_policies';

    public function countryRelation() {
        return $this->hasMany('OlaHub\UserPortal\Models\ManyToMany\exchRefundPolicyCountries','policy_id');
    }

}
