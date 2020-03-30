<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerItemAttrValue extends Model {

    protected $table = 'designer_item_attribute_values';


    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesignerItems','item_id');
    }

    public function valueMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\AttrValue','item_attribute_value_id');
    }
    
}
