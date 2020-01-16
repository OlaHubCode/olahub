<?php

namespace OlaHub\UserPortal\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class DesginerItems extends Eloquent {

    protected $connection = 'mongo';
    protected $collection = 'designers_items';
    
    static function checkPrice($item, $final = false, $withCurr = true, $countryId = false){
        $return["productPrice"] = isset($item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_price, $withCurr) : 0;
        $return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_price, $withCurr);
        $return["productHasDiscount"] = false;
        if (isset($item->item_original_price) && $item->item_original_price && strtotime($item->discount_start_date) <= time() && strtotime($item->discount_end_date) >= time()) {
            $return["productPrice"] = isset($item->item_original_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_original_price, $withCurr) : 0;
            $return["productHasDiscount"] = true;
        }

        if ($final) {
            return $return["productDiscountedPrice"];
        }
        return $return;
    }
    
    static function searchItems($q = 'a', $count = 15) {
        $items = DesginerItems::where("item_title", 'LIKE', "%$q%")->orWhere("item_description",  'LIKE', "%$q%");
        if ($count > 0) {
            return $items->paginate($count);
        }else{
            return $items->count();
        }
    }

}
