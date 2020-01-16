<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class AttrValue extends Model {

    protected $table = 'catalog_attribute_values';

    public function attributeMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Attribute', 'product_attribute_id');
    }

    public function valueItemsData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'item_attribute_value_id');
    }
}
