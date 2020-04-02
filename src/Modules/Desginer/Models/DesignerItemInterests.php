<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class DesignerItemInterests extends Model
{
    protected $table = 'designer_item_interests';

    public function itemsMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesignerItems', 'item_id');
    }

    public function interestMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Interest', 'interest_id');
    }
}
