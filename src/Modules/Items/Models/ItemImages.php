<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemImages extends Model {

    protected $table = 'catalog_item_images';

    public function country() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country');
    }
    
    public function merchant() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Merchant','merchant_id');
    }
    
    public function itemData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem','item_id');
    }

}
