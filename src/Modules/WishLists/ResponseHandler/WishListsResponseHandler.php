<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\WishList;
use League\Fractal;

class WishListsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $item;

    public function transform(WishList $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->prepareItemData();
        return $this->return;
    }

    private function prepareItemData() {
        if ($this->data->item_type == 'store') {
            $this->item = $this->data->itemsMainData;
            //var_dump($this->item);
            if($this->item){
               $this->setItemMainData();
               $this->setAddData();
               $this->setItemImageData();
               $this->setPriceData();
               $this->setItemOwnerData(); 
            }
            
        } else {
            $this->item = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_ids', [$this->data->item_id])->first();
            $this->getDesignerItemData();
        }
    }

    private function setDefaultData() {

        // Ocassion data (Name - Slug)
        $occassion = \OlaHub\UserPortal\Models\Occasion::where("id", $this->data->occasion_id)->first();
        $this->return = [
            "occasionName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, "name"),
            "occasionSlug" => isset($occassion->occasion_slug) ? $occassion->occasion_slug : false,
            "occasionImage" => isset($occassion->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassion->logo_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
        ];
    }

    private function setItemMainData() {
        $itemName = isset($this->item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL;
        $itemDescription = isset($this->item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'description') : NULL;
        $this->return["productID"] = isset($this->item->id) ? $this->item->id : 0;
        $this->return["productSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->item, 'item_slug', $itemName);
        $this->return["productName"] = $itemName;
        $this->return["productDescription"] = str_limit(strip_tags($itemDescription), 350, '.....');
        $this->return["productInStock"] = \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item);
    }

    private function setItemImageData() {
        $images = isset($this->item->images) ? $this->item->images : [];
        if (count($images) > 0 && $images->count() > 0) {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData() {
        $this->return["productPrice"] = isset($this->item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->price) : 0;
        $this->return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return["productHasDiscount"] = false;
        if (isset($this->item->has_discount) && $this->item->has_discount && strtotime($this->item->discounted_price_start_date) <= time() && strtotime($this->item->discounted_price_end_date) >= time()) {
            $this->return["productDiscountedPrice"] = isset($this->item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->discounted_price) : 0;
            $this->return["productHasDiscount"] = true;
        }
    }

    private function setItemOwnerData() {
        $merchant = $this->item->merchant;
        $this->return["productOwner"] = isset($merchant->id) ? $merchant->id : NULL;
        $this->return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($merchant, 'company_legal_name');
        $this->return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($merchant, 'merchant_slug', $this->return["productOwnerName"]);
    }

    private function setAddData() {
        $this->return['productWishlisted'] = '0';
        $this->return['productLiked'] = '0';
        $this->return['productInCart'] = '0';

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $this->item->id)->count() > 0) {
            $this->return['productWishlisted'] = '1';
        }

        //like
        if (\OlaHub\UserPortal\Models\LikedItems::where('item_id', $this->item->id)->count() > 0) {
            $this->return['productLiked'] = '1';
        }

        //Cart
        $itemID = $this->item->id;
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use($itemID) {
                    $q->where('item_id', $itemID);
                })->count() > 0) {
            $this->return['productInCart'] = '1';
        }
    }
    
    
    
    private function getDesignerItemData() {

        $user = app('session')->get('tempID') ? \OlaHub\DesignerCorner\Additional\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first() : false;

        $this->return["productID"] = isset($this->item->item_id) ? $this->item->item_id : 0;
        $this->return["productSlug"] = isset($this->item->item_slug) ? $this->item->item_slug : null;
        $this->return["productName"] = isset($this->item->item_title) ? $this->item->item_title : null;
        $this->return["productDescription"] = isset($this->item->item_description) ? $this->item->item_description : null;
        $this->return["productInStock"] = isset($this->item->item_stock) ? $this->item->item_stock : 0;
        $this->return["productOwner"] = isset($this->item->designer_id) ? $this->item->designer_id : 0;
        $this->return["productOwnerName"] = isset($this->item->designer_name) ? $this->item->designer_name : null;
        $this->return["productOwnerSlug"] = isset($this->item->designer_slug) ? $this->item->designer_slug : null;
        
        $this->setDesignerPriceData($this->item);
        $this->setDesignerItemImageData($this->item);
        $this->setDesignerAddData();
        $item = false;
        
        if ($this->item->item_id != $this->data->item_id) {

            foreach ($this->item->items as $one) {
                $oneItem = (object) $one;
                if (isset($oneItem->item_id) && $oneItem->item_id == $this->data->item_id) {
                    $item = $oneItem;
                }
            }
        }
            
        if ($item) {
            $this->return["productID"] = isset($item->item_id) ? $item->item_id : 0;
            $this->return["productSlug"] = isset($item->item_slug) ? $item->item_slug : null;
            $this->setDesignerPriceData($item);
            $this->setDesignerItemImageData($item);
            $this->setDesignerAddData();
        }

    }
    
    private function setDesignerItemImageData($item) {
        $images = [];
        if(isset($item->item_images)){
            $images = $item->item_images;
        }elseif (isset($item->item_image)) {
            $images = $item->item_image;
        }
        if (count($images) > 0 && $images->count() > 0) {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setDesignerPriceData($item) {
        $this->return["productPrice"] = isset($item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($item->item_price) : 0;
        $this->return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return["productHasDiscount"] = false;
        if (isset($item->item_original_price) && $item->item_original_price && strtotime($item->discount_start_date) <= time() && strtotime($item->discount_end_date) >= time()) {
            $this->return["productDiscountedPrice"] = isset($this->item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->item_price) : 0;
            $this->return["productPrice"] = isset($item->item_original_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($item->item_original_price) : 0;
            $this->return["productHasDiscount"] = true;
        }
    }
    
    private function setDesignerAddData() {
        $this->return['productWishlisted'] = '0';
        $this->return['productLiked'] = '0';
        $this->return['productInCart'] = '0';

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $this->item->item_id)->count() > 0) {
            $this->return['productWishlisted'] = '1';
        }

        //like
        if (\OlaHub\UserPortal\Models\LikedItems::where('item_id', $this->item->item_id)->count() > 0) {
            $this->return['productLiked'] = '1';
        }

        //Cart
        $itemID = $this->item->item_id;
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use($itemID) {
                    $q->where('item_id', $itemID);
                })->count() > 0) {
            $this->return['productInCart'] = '1';
        }
    }

}
