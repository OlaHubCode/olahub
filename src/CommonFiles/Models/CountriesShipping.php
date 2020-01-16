<?php

/**
 * Countries model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class CountriesShipping extends \Illuminate\Database\Eloquent\Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'countries_shipping_fees';
    protected $guarded = array('created_at', 'updated_at', 'deleted_at', 'id', 'name', 'two_letter_iso_code', 'three_letter_iso_code', 'language_id', 'currency_id', 'is_published', 'is_supported');

    protected static function boot() {
        parent::boot();
    }

    static function getShippingFees($from, $to) {
        $return = 0;
        $shipping = CountriesShipping::where("country_from", $from)
                ->where("country_to", $to)
                ->first();
        if ($shipping) {
            $shippingFees = $shipping->total_shipping;
            $return = CurrnciesExchange::getCurrncy("USD", app("session")->get("def_currency") ? app("session")->get("def_currency")->code : "JOD", $shippingFees);
        }

        return $return;
    }

}
