<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAttrValue extends Model {

    protected $table = 'catalog_item_attribute_values';


    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem','item_id');
    }

    public function valueMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\AttrValue','item_attribute_value_id');
    }
    
}
