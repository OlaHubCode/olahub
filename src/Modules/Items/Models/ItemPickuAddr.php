<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPickuAddr extends Model {
    use SoftDeletes;

    protected $table = 'catalog_item_stors';

    public function storeData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemStore','store_id');
    }

    public function pickupData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\StorePickups','pickup_address_id');
    }

}
