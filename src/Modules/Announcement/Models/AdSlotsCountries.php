<?php

/**
 * Campigns model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\Models;

use Jenssegers\Mongodb\Eloquent\HybridRelations;
use Illuminate\Database\Eloquent\Model;

class AdSlotsCountries extends Model {

    use HybridRelations;

    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);

        static::addGlobalScope('country', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('country_id', app('session')->get('def_country')->id);
            $builder->has('mainSlotDetails');
        });
    }

    protected $table = 'campaign_slot_prices';

    function mainSlotDetails() {
        return $this->belongsTo('OlaHub\Models\AdSlots', 'slot_id');
    }

}
