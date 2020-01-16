<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model {

    protected $table = 'shopping_carts';

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('countryUser', function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->where('user_id', app('session')->get('tempID'));
        });

        static::saving(function ($query) {
            if ($query->country_id == 0) {
                if (isset($query->celebration_id) && $query->celebration_id > 0) {
                    $celebration = CelebrationModel::find($query->celebration_id);
                    if($celebration){
                        $query->country_id = $celebration->country_id;
                    }
                }else{
                    $query->country_id = app("session")->get("def_country")->id;
                }
            }
            if (!isset($query->celebration_id) && !($query->celebration_id > 0)) {
                if (!isset($query->user_id) && !$query->user_id) {
                    $query->user_id = app('session')->get('tempID');
                }
            }
        });
    }

    static $columnsMaping = [
        'itemID' => [
            'column' => 'item_id',
            'type' => 'number',
            'relation' => false,
            'validation' => 'required'
        ],
        'itemQuantity' => [
            'column' => 'quantity',
            'type' => 'number',
            'relation' => false,
            'validation' => 'numeric'
        ],
    ];

    public function cartDetails() {
        return $this->hasMany('OlaHub\UserPortal\Models\CartItems', 'shopping_cart_id');
    }

    public function celebration() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CelebrationModel', 'celebration_id');
    }

    public function calendar() {
        return $this->belongsTo('OlaHub\UserPortal\Models\CalendarModel', 'calendar_id');
    }

    static function getCartSubTotal(Cart $cart, $withCurr = true) {
        $total = 0;
        if (!$withCurr) {
            $items = CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
        } else {
            $items = $cart->cartDetails;
        }
        if ($items->count() > 0) {
            foreach ($items as $item) {
                switch ($item->item_type) {
                    case "store":
                        if ($cart->celebration_id > 0) {
                            $mainItem = CatalogItem::withoutGlobalScope('country')->where('id', $item->item_id)->first();
                        } else {
                            $mainItem = CatalogItem::where('id', $item->item_id)->first();
                        }
                        if ($mainItem) {
                            $itemPrice = CatalogItem::checkPrice($mainItem, TRUE, FALSE);
                            if ($itemPrice != $item->unit_price) {
                                $item->unit_price = $itemPrice;
                                $item->total_price = $itemPrice * $item->quantity;
                                $item->save();
                            }
                            $total += $item->total_price;
                        }
                        break;
                    case "designer":
                        $mainItem = DesginerItems::whereIn('item_ids', [(string) $item->item_id, (int)$item->item_id])->first();
                        if ($mainItem) {
                            $itemDes = false;
                            if (isset($mainItem->items) && count($mainItem->items) > 0) {
                                foreach ($mainItem->items as $oneItem) {
                                    if ($oneItem["item_id"] == $item->item_id) {
                                        $itemDes = (object) $oneItem;
                                    }
                                }
                            }
                            if (!$itemDes) {
                                $itemDes = $mainItem;
                            }
                            $itemPrice = DesginerItems::checkPrice($itemDes, TRUE, FALSE);
                            if ($itemPrice != $item->unit_price) {
                                $item->unit_price = $itemPrice;
                                $item->total_price = $itemPrice * $item->quantity;
                                $item->save();
                            }
                            $total += $item->total_price;
                        }
                        break;
                }
            }
        }
        if ($cart->total_price != $total) {
            $cart->total_price = $total;
            $cart->save();
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($cart->total_price, $withCurr);
        return $return;
    }

    static function getUserCart($userID = false) {
        if ($userID > 0) {
            $cart = Cart::withoutGlobalScope('countryUser')->where('user_id', $userID)->first();
        } else {
            $cart = Cart::first();
        }

        if (!$cart) {
            $cart = new Cart;
            $cart->shopping_cart_date = date('Y-m-d h:i');
            $cart->total_price = '0.00';
            $cart->country_id = 5;
            if ($userID > 0) {
                $cart->user_id = $userID;
            }
            $cart->save();
        }

        return $cart;
    }

    static function getCelebrationCart($celebration = false) {
        if ($celebration) {
            $cart = Cart::withoutGlobalScope('countryUser')->where('celebration_id', $celebration->id)->where('country_id', $celebration->country_id)->first();

            if (!$cart) {
                $cart = new Cart;
                $cart->shopping_cart_date = date('Y-m-d h:i');
                $cart->total_price = '0.00';
                $cart->celebration_id = $celebration->id;
                $cart->country_id = $celebration->country_id;
                $country = \OlaHub\UserPortal\Models\Country::where('id', $celebration->country_id)->first();
                $cart->currency_id = $country->currency_id;
                $cart->save();
            }

            return $cart;
        }
        return false;
    }

    static function checkDesignersShipping($cart) {
        $return = 0;
        $country = $cart->country_id;
        $addedCountries = [];
        $designerItems = CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
        foreach ($designerItems as $item) {
            if ($item->item_type == "designer") {
                $designer = Designer::find($item->merchant_id);
                if ($designer && !in_array($designer->country_id, $addedCountries)) {
                    $designerCountry = $designer->country_id;
                    $shippingFees = CountriesShipping::getShippingFees($designerCountry, $country);
                    $return += $shippingFees;
                    $item->shipping_fees = $shippingFees;
                    $item->save();
                    $addedCountries[] = $designer->country_id;
                }
            }
        }
        $cart->shipment_fees = $return;
        $cart->save();
        return $return;
    }

}
