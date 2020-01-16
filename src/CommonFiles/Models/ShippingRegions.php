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

class ShippingRegions extends \Illuminate\Database\Eloquent\Model {



    protected $table = 'shipping_regions';

    //     public function country() {
    //     	return $this->belongsTo('OlaHub\UserPortal\Models\ShippingCountries');
    // }

    //     public function city() {
    //     	return $this->hasMany('OlaHub\UserPortal\Models\ShippingCities');
    // }
    
}