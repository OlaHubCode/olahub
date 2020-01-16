<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CatalogItem;
use League\Fractal;

class LikedItemsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;
    private $item;

    public function transform(CatalogItem $data) {
		$this->data = $data;
        $this->item = $this->data;
        $this->setDefaultData();
        $this->setItemMainData();
        $this->setAddData();
        $this->setItemImageData();
        $this->setPriceData();
        $this->setItemOwnerData();
        return $this->return;
    }

    private function setDefaultData() {

        /*$this->return = [
            "listID" => isset($this->data->id) ? $this->data->id : 0,
        ];*/
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
        if ($this->item->has_discount && strtotime($this->item->discounted_price_start_date) <= time() && strtotime($this->item->discounted_price_end_date) >= time()) {
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
        $post = \OlaHub\UserPortal\Models\Post::where('item_slug', $this->item->item_slug)->first();
        if (in_array(app('session')->get('tempID'), $post->likes)) {
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

}
