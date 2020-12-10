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

class ShippingCities extends \Illuminate\Database\Eloquent\Model {


    protected $table = 'cities';

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        static::addGlobalScope('city', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('is_published', 1);
        });
    }

    //     public function country() {
    //     return $this->belongsTo('OlaHub\UserPortal\Models\ShippingCountries');
    // }

    //     public function region() {
    //     return $this->belongsTo('OlaHub\UserPortal\Models\ShippingRegions');
    // }
  
}