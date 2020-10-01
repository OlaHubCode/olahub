<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttrValue extends Model {

    use SoftDeletes;
    protected $table = 'catalog_attribute_values';

    public function attributeMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Attribute', 'product_attribute_id');
    }

    public function valueItemsData() {
        return $this->hasMany('OlaHub\UserPortal\Models\ItemAttrValue', 'item_attribute_value_id');
    }

    public function valueDesignerData() {
        return $this->hasMany('OlaHub\UserPortal\Models\DesignerItemAttrValue', 'value_id');
    }
}
