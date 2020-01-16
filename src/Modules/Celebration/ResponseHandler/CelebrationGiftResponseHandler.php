<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CartItems;
use League\Fractal;

class CelebrationGiftResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $item;

    public function transform(CartItems $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setGiftOwnerImageData();
        $this->setLikersData();
        return $this->return;
    }

    private function setDefaultData() {
        switch ($this->data->item_type) {
            case "store":
                $this->item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope('country')->where('id', $this->data->item_id)->first();
                $this->return = [
                    "celebrationGiftId" => isset($this->data->id) ? $this->data->id : 0,
                    "celebrationGiftType" => "store",
                    "celebrationGiftOwner" => $this->data->created_by == app('session')->get('tempID') ? TRUE : FALSE,
                    "celebrationItem" => isset($this->item->id) ? $this->item->id : 0,
                    "celebrationItemName" => isset($this->item) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL,
                    "celebrationItemSlug" => isset($this->item->item_slug) ? $this->item->item_slug : NULL,
                    "celebrationItemSKU" => isset($this->item->sku) ? $this->item->sku : NULL,
                    "celebrationItemInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item),
                ];
                $this->setDefImageData();
                $this->setPriceData();
                break;
            case "designer":
                $itemMain = \OlaHub\UserPortal\Models\DesginerItems::whereIn("item_ids", [$this->data->item_id])->first();
                if ($itemMain) {
                    $item = false;
                    if (isset($itemMain->items) && count($itemMain->items) > 0) {
                        foreach ($itemMain->items as $oneItem) {
                            if ($oneItem["item_id"] == $this->data->item_id) {
                                $item = (object) $oneItem;
                            }
                        }
                    }
                    if (!$item) {
                        $item = $itemMain;
                    }
                    $this->return = [
                        "celebrationGiftId" => isset($this->data->id) ? $this->data->id : 0,
                        "celebrationGiftType" => "designer",
                        "celebrationGiftOwner" => $this->data->created_by == app('session')->get('tempID') ? TRUE : FALSE,
                        "celebrationItem" => isset($item->item_id) ? $item->item_id : 0,
                        "celebrationItemName" => $itemMain->item_title,
                        "celebrationItemSlug" => isset($item->item_slug) ? $item->item_slug : NULL,
                        "celebrationItemSKU" => isset($itemMain->sku) ? $itemMain->sku : NULL,
                        "celebrationItemInStock" => isset($item->item_stock) ? $item->item_stock : 1,
                    ];
                    $this->setDesignerDefImageData($item);
                    $this->setDesignerPrice($item);
                }
                break;
        }
    }

    private function setDefImageData() {
        $images = $this->item->images;
        if ($images->count() > 0) {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDesignerDefImageData($item) {
        $images = isset($item->item_image) ? $item->item_image : (isset($item->item_images) ? $item->item_images : false);
        if ($images && count($images) > 0) {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]);
        } else {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setGiftOwnerImageData() {
        $giftOwner = \OlaHub\UserPortal\Models\UserModel::where('id', $this->data->created_by)->first();
        $this->return["celebrationGiftOwnerName"] = isset($giftOwner) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($giftOwner, 'name') : NULL;
        if (isset($giftOwner->profile_picture)) {
            $this->return['celebrationGiftOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($giftOwner->profile_picture);
        } else {
            $this->return['celebrationGiftOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData() {
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('id', $this->data->shopping_cart_id)->first();
        $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $this->data->shopping_cart_id)->where('item_id', $this->item->id)->first();
        $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $cart->celebration_id)->first();
        $return = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($this->item, false, true, $celebration->country_id);
        $this->return['celebrationItemPrice'] = $return['productPrice'];
        $this->return['celebrationItemQuantity'] = $cartDetails->quantity;
        $this->return['celebrationItemTotalPrice'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($cartDetails->total_price, true, $celebration->country_id);
        $this->return['celebrationItemDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['celebrationItemHasDiscount'] = $return['productHasDiscount'];
    }

    private function setDesignerPrice($item) {
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('id', $this->data->shopping_cart_id)->first();
        $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $this->data->shopping_cart_id)->where('item_id', $item->item_id)->first();
        $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $cart->celebration_id)->first();
        $return = \OlaHub\UserPortal\Models\DesginerItems::checkPrice($item, false, true, $celebration->country_id);
        $this->return['celebrationItemPrice'] = $return['productPrice'];
        $this->return['celebrationItemQuantity'] = $cartDetails->quantity;
        $this->return['celebrationItemTotalPrice'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($cartDetails->total_price, true, $celebration->country_id);
        $this->return['celebrationItemDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['celebrationItemHasDiscount'] = $return['productHasDiscount'];
    }

    private function setLikersData() {
        $participantLikers = unserialize($this->data->paricipant_likers);
        $this->return['currentLike'] = FALSE;
        $this->return['totalLikers'] = 0;
        $likers = [];
        if ($participantLikers && count($participantLikers) > 0) {
            $usersData = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $participantLikers['user_id'])->get();
            $this->return['totalLikers'] = count($usersData);
            $likers = [];
            foreach ($usersData as $userData) {
                if ($userData->id != app('session')->get('tempID')) {
                    $likers[] = [
                        "userId" => $userData->id,
                        "userName" => isset($userData->first_name) ? $userData->first_name . ' ' . $userData->last_name : NULL
                    ];
                } else {
                    $this->return['currentLike'] = TRUE;
                }
            }
        }
        $this->return['Likers'] = $likers;
    }

}
