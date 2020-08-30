<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemAttrValue extends Model {
    use SoftDeletes;

    protected $table = 'catalog_item_attribute_values';


    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem','item_id');
    }

    public function valueMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\AttrValue','item_attribute_value_id');
    }
    
}
