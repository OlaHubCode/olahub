<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemPickuAddr extends Model {

    protected $table = 'catalog_item_stors';

    public function storeData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemStore','store_id');
    }

    public function pickupData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\StorePickups','pickup_address_id');
    }

}
