<?php

namespace OlaHub\UserPortal\Models;

class MerchantInvite extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }


    protected $table = 'merchant_invites';


}
