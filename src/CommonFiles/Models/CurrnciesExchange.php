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

class CurrnciesExchange extends \Illuminate\Database\Eloquent\Model {

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

    protected $table = 'currencies_exchange_rates';
    protected $guarded = array('created_at', 'updated_at', 'deleted_at', 'id','name','two_letter_iso_code','three_letter_iso_code','language_id','currency_id','is_published','is_supported');
    
    protected static function boot() {
        parent::boot();
    }
    
    static function getCurrncy($from, $to, $amount){
        $return = $amount;
        $currnecyExchange = CurrnciesExchange::where("currency_from", $from)
                ->where("currency_to", $to)
                ->first();
        if($currnecyExchange){
            $return = $amount * $currnecyExchange->exchange_rate;
        }
        return $return;
    }
}