<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class StorePickups extends Model {

    protected $table = 'store_pickup_addresses';

    public function storeData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\ItemStore','store_id');
    }

}
