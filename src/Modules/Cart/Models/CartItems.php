<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class CartItems extends Model {

    protected $table = 'shopping_carts_details';

    protected static function boot() {
        parent::boot();

//        static::saved(function ($query) {
//            $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->find($query->shopping_cart_id);
//            $cart->total_price = Cart::getCartSubTotal($cart, TRUE);
//        });
    }

    public function itemsMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function cartMainData() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Cart', 'shopping_cart_id');
    }

    static function addItemToCartByID($cart, $itemID, $customImage, $customeText, $cartType,$quantity = 1) {
        
        switch ($cartType){
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")->whereHas('merchant', function ($q) use($cart) {
                    $q->country_id = $cart->country_id;
                })->find($itemID);
                $checkItem = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')
                        ->where('item_id', $itemID)
                        ->where('shopping_cart_id', $cart->id)
                        ->first();
                $customData=[
                    'image'=>$customImage,
                    'text'=>$customeText
                ];
                if ($item) {
                    $cartItems = $checkItem ? $checkItem : new \OlaHub\UserPortal\Models\CartItems;
                    $cartItems->item_id = $item->id;
                    $cartItems->merchant_id = $item->merchant_id;
                    $cartItems->store_id = $item->store_id;
                    $cartItems->shopping_cart_id = $cart->id;
                    $cartItems->item_type = $cartType;
                    $cartItems->customize_data = serialize($customData);
                    $cartItems->unit_price = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item, TRUE);
                    $cartItems->quantity = $quantity;
                    $cartItems->total_price = (double) $cartItems->unit_price * $cartItems->quantity;
                    if(!$cart->user_id){
                       $cartItems->paricipant_likers = serialize(["user_id" => [app('session')->get('tempID')]]);
                       $cartItems->created_by = app('session')->get('tempID');
                    }
                    $cartItems->save();
                }
                
                
                break;
            case "designer":
                $itemMain = \OlaHub\UserPortal\Models\DesginerItems::whereIn("item_ids", [(string)$itemID, (int) $itemID])->first();
                if ($itemMain) {
                    $item = false;
                    if (isset($itemMain->items) && count($itemMain->items) > 0) {
                        foreach ($itemMain->items as $oneItem) {
                            if ($oneItem["item_id"] == $itemID) {
                                $item = (object) $oneItem;
                            }
                        }
                    }
                    if (!$item) {
                        $item = $itemMain;
                    }
                    $checkItem = $cart->cartDetails()->where('item_id', $item->item_id)->where("item_type", $cartType)->first();
                    if ($checkItem) {
                        $cartItems = $checkItem;
                    } else {
                        $cartItems = new \OlaHub\UserPortal\Models\CartItems;
                    }
                    
                    $customData=[
                        'image'=>$customImage,
                        'text'=>$customeText
                    ];
                    $cartItems->customize_data = serialize($customData);
                    $cartItems->item_id = $item->item_id;
                    $cartItems->shopping_cart_id = $cart->id;
                    $cartItems->merchant_id = $itemMain->designer_id;
                    $cartItems->store_id = $itemMain->designer_id;
                    $cartItems->item_type = $cartType;
                    $cartItems->unit_price = $item->item_price;
                    $cartItems->quantity = $quantity;
                    $cartItems->total_price = (double) $cartItems->unit_price * $cartItems->quantity;
                    if(!$cart->user_id){
                       $cartItems->paricipant_likers = serialize(["user_id" => [app('session')->get('tempID')]]);
                       $cartItems->created_by = app('session')->get('tempID');
                    }
                    $cartItems->save();
                }
                
                
                break;
        }
        
        
    }

}
