<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerItemImages extends Model {

    protected $table = 'designer_item_images';

    public function itemData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesginerItems','item_id');
    }
}
