<?php

namespace OlaHub\UserPortal\Models;

class SellWithUsUnsupport extends \Illuminate\Database\Eloquent\Model {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }


    protected $table = 'sell_with_us_unsupported_countries';


}
