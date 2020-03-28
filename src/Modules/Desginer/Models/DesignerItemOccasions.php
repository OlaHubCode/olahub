<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerItemOccasions extends Model {
    protected $table = 'designer_item_occasions';

    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesginerItems', 'item_id');
    }

    public function occasionMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Occasion', 'occasion_id');
    }

}
