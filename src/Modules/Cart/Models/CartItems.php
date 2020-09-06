<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItems extends Model
{
    use SoftDeletes;
    protected $table = 'shopping_carts_details';

    protected static function boot()
    {
        parent::boot();

        //        static::saved(function ($query) {
        //            $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->find($query->shopping_cart_id);
        //            $cart->total_price = Cart::getCartSubTotal($cart, TRUE);
        //        });
    }

    public function itemsMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\CatalogItem', 'item_id');
    }

    public function itemsDesignerData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\DesignerItems', 'item_id');
    }

    public function itemsData()
    {
        return $this->hasMany('OlaHub\UserPortal\Models\CatalogItem', 'id', 'item_id');
    }

    public function cartMainData()
    {
        return $this->belongsTo('OlaHub\UserPortal\Models\Cart', 'shopping_cart_id');
    }

    static function addItemToCartByID($cart, $itemID, $customImage, $customeText, $cartType, $quantity = 1)
    {

        switch ($cartType) {
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")->whereHas('merchant', function ($q) use ($cart) {
                    $q->country_id = $cart->country_id;
                })->find($itemID);
                $checkItem = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')
                    ->where('item_id', $itemID)
                    ->where('item_type', 'store')
                    ->where('shopping_cart_id', $cart->id)
                    ->first();
                $customData = [
                    'image' => $customImage,
                    'text' => $customeText
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
                    $cartItems->total_price = (float) $cartItems->unit_price * $cartItems->quantity;
                    if (!$cart->user_id) {
                        $cartItems->paricipant_likers = serialize(["user_id" => [app('session')->get('tempID')]]);
                        $cartItems->created_by = app('session')->get('tempID');
                    }
                    $cartItems->save();
                }


                break;
            case "designer":
                $item = \OlaHub\UserPortal\Models\DesignerItems::find($itemID);
                $checkItem = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')
                    ->where('item_id', $itemID)
                    ->where('shopping_cart_id', $cart->id)
                    ->where('item_type', 'designer')
                    ->first();
                $customData = [
                    'image' => $customImage,
                    'text' => $customeText
                ];
                if ($item) {
                    $cartItems = $checkItem ? $checkItem : new \OlaHub\UserPortal\Models\CartItems;
                    $cartItems->item_id = $item->id;
                    $cartItems->merchant_id = $item->designer_id;
                    $cartItems->store_id = $item->designer_id;
                    $cartItems->shopping_cart_id = $cart->id;
                    $cartItems->item_type = $cartType;
                    $cartItems->customize_data = serialize($customData);
                    $cartItems->unit_price = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item, TRUE);
                    $cartItems->quantity = $quantity;
                    $cartItems->total_price = (float) $cartItems->unit_price * $cartItems->quantity;
                    if (!$cart->user_id) {
                        $cartItems->paricipant_likers = serialize(["user_id" => [app('session')->get('tempID')]]);
                        $cartItems->created_by = app('session')->get('tempID');
                    }
                    $cartItems->save();
                }
                break;
        }
    }

    static function checkIfItemsNotVoucher($items)
    {
        foreach ($items as $item) {
            if (@$item['items_data']) {
                if (!$item['items_data'][0]['is_voucher'])
                    return false;
            } else {
                return false;
            }
        }
        return true;
    }

    static function checkIfItemsHasVoucher($items)
    {
        foreach ($items as $item) {
            if (@$item['items_data']) {
                if ($item['items_data'][0]['is_voucher'])
                    return true;
            }
        }
    }
}
