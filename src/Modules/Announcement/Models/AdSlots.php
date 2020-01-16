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

class AdSlots extends Model {

    use HybridRelations;

    protected $table = 'campaign_slots';

    function parentSlotDetails() {
        return $this->belongsTo('OlaHub\Models\AdSlots', 'parent_id');
    }

}
