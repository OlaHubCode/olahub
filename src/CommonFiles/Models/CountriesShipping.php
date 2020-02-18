<?php

/**
 * Countries model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class CountriesShipping extends \Illuminate\Database\Eloquent\Model
{

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    protected $table = 'countries_shipping_fees';
    protected $guarded = array('created_at', 'updated_at', 'deleted_at', 'id', 'name', 'two_letter_iso_code', 'three_letter_iso_code', 'language_id', 'currency_id', 'is_published', 'is_supported');

    protected static function boot()
    {
        parent::boot();
    }

    static function getShippingFees($from, $to = NULL)
    {
        $country = \OlaHub\UserPortal\Models\Country::find($from);
        $currency = $country->currencyData;

        if (!$to)
            $to = $from;
        // echo $to. " - " . $from;
        $return = 0;
        $shipping = CountriesShipping::where("country_from", $from)
            ->where("country_to", $to)
            ->where("is_shipping", 1)
            ->first();
        if (!$shipping) {
            $shipping = CountriesShipping::where("country_from", $from)
                ->where("country_to", 0)
                ->where("is_shipping", 1)
                ->first();
        }
        $shippingFees = $shipping->total_shipping;
        $return = CurrnciesExchange::getCurrncy("USD", $currency->code, $shippingFees);
        // $return = CurrnciesExchange::getCurrncy("USD", app("session")->get("def_currency") ? app("session")->get("def_currency")->code : "JOD", $shippingFees);

        return $return;
    }
}
