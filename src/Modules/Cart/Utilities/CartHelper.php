<?php

namespace OlaHub\UserPortal\Helpers;

class CartHelper extends OlaHubCommonHelper {

    function setSessionCartData($userID, $cartRequest, $returnCart = false) {
        if ($cartRequest) {
            $cart = \OlaHub\UserPortal\Models\Cart::getUserCart($userID);
            $this->addSessionCartProducts($cart, $cartRequest);
//            if ($created) {
//                return $returnCart ? \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($cart, '\OlaHub\UserPortal\ResponseHandlers\CartResponseHandler') : TRUE;
//            }
        }
//        return $returnCart ? [] : FALSE;
    }

    function addSessionCartProducts($cart, $cartRequest) {
        if ($cartRequest && count($cartRequest)) {
            foreach ($cartRequest as $requestItem) {
                $id = isset($requestItem['productID']) ? $requestItem['productID'] : (isset($requestItem['productId']) ? isset($requestItem['productId']) : 0);
                $img = isset($requestItem['customImage']) ? $requestItem['customImage'] : '';
                $txt = isset($requestItem['customeText']) ? $requestItem['customeText'] : '';
                $type = isset($requestItem['itemType']) ? $requestItem['itemType'] : (isset($requestItem['productType']) ? $requestItem['productType'] : 'store');
                $qty = isset($requestItem['quantity']) ? $requestItem['quantity'] : (isset($requestItem['productQuantity']) ? $requestItem['productQuantity'] : 1);
                \OlaHub\UserPortal\Models\CartItems::addItemToCartByID($cart, $id, $img, $txt, $type, $qty);
            }
        }
    }

    function setCelebrationCartData($celebration, $cartRequest = false) {
        if ($celebration) {
            $cart = \OlaHub\UserPortal\Models\Cart::getCelebrationCart($celebration);
            if ($cart) {
                $this->addCelebrationCartProducts($cart, $cartRequest);
                return $cart;
            }
        }
        return FALSE;
    }

    function addCelebrationCartProducts($cart, $cartRequest) {
        if ($cartRequest && count($cartRequest)) {
            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->where('celebration_id', $cart->celebration_id)->first();
            if ($participant) {
                \OlaHub\UserPortal\Models\CartItems::addItemToCartByID($cart, $cartRequest['itemId'], '', '', isset($cartRequest['itemQuantity']) ? $cartRequest['itemQuantity'] : 1);
            }
        }
    }

    function checkOutOfStockInCartItem($cartId = false, $celebration = false) {
        if ($cartId > 0) {
            $itemsCart = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope("countryUser")->where('shopping_cart_id', $cartId)->get();
            if ($itemsCart->count() > 0) {
                foreach ($itemsCart as $item) {
                    switch ($item->item_type) {
                        case "store":
                            if ($celebration) {
                                $itemData = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")
                                                ->whereHas('merchant', function ($merchantQ) use($celebration) {
                                                    $merchantQ->withoutGlobalScope('country');
                                                    $merchantQ->where('country_id', $celebration->country_id);
                                                })
                                                ->where('id', $item->item_id)->first();
                            } else {
                                $itemData = \OlaHub\UserPortal\Models\CatalogItem::where('id', $item->item_id)->first();
                            }
                            if ($itemData) {
                                $inStock = \OlaHub\UserPortal\Models\CatalogItem::checkStock($itemData);
                                if ($inStock < $item->quantity && $inStock > 0) {
                                    $item->quantity = $inStock;
                                    $item->save();
                                } elseif ($inStock < 1) {
                                    $item->delete();
                                }
                            } else {
                                $item->delete();
                            }
                            break;
                        case "designer":

                            break;
                    }
                }
            }
        }
    }
    
    
    function setNotLoggedCartTotal($cartCookies){
        $return = [];
        $subTotal = 0;
        $shippingFees = 0;
        foreach ($cartCookies as $cartCookie){
            $total = 0;
            if($cartCookie->productType == 'designer'){
               $mainItem = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_ids', [$cartCookie->productId])->first();
                if ($mainItem) {
                    $itemDes = false;
                    if (isset($mainItem->items) && count($mainItem->items) > 0) {
                        foreach ($mainItem->items as $oneItem) {
                            if ($oneItem["item_id"] == $cartCookie->productId) {
                                $itemDes = (object) $oneItem;
                            }
                        }
                    }
                    if (!$itemDes) {
                        $itemDes = $mainItem;
                    }
                    $itemPrice = \OlaHub\UserPortal\Models\DesginerItems::checkPrice($itemDes, TRUE, FALSE);
                    $total += $itemPrice * (isset($cartCookie->productQuantity) ? $cartCookie->productQuantity : 1);
                } 
            } else {
                $mainItem = \OlaHub\UserPortal\Models\CatalogItem::where('id', $cartCookie->productId)->first();
                if ($mainItem) {
                    $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($mainItem, TRUE, FALSE);
                    $total += $itemPrice * $cartCookie->productQuantity;
                }
            }
            $subTotal += \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total, false);
            $shippingFees += \OlaHub\UserPortal\Models\CatalogItem::where('id', $cartCookie->productId)->where('is_shipment_free', '0')->first() ? 3.5 : 0;
            
        }
        $totalVal = (double) $subTotal + $shippingFees;
        $return[] = ['label' => 'subtotal', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($subTotal, true), 'className' => "subtotal"];
        $return[] = ['label' => 'shippingFees', 'value' => $shippingFees ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($shippingFees) : 'free', 'className' => "shippingFees"];
        $return[] = ['label' => 'total', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($totalVal), 'className' => "total"];
        return $return;
    }

}
