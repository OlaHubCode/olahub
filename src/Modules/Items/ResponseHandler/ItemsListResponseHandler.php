<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CatalogItem;
use League\Fractal;

class ItemsListResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;

    public function transform(CatalogItem $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setPriceData();
        $this->setWishlistData();
        // $this->setMerchantData();
        //         $this->setAddData();
        // $this->setDefImageData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $itemName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $itemDescription = isset($this->data->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'description') : NULL;
        $images = $this->data->images;
        $this->return = [
            "type" => "store",
            "productID" => isset($this->data->id) ? $this->data->id : 0,
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'item_slug', $itemName),
            "productSKU" => isset($this->data->sku) ? $this->data->sku : NULL,
            "productName" => $itemName,
            "productDescription" => $itemDescription,
            "productInStock" => ($this->data->qu ? $this->data->qu : CatalogItem::checkStock($this->data)),
            // "productInStock" => CatalogItem::checkStock($this->data),
            "productIsNew" => CatalogItem::checkIsNew($this->data),
            "productImage" => isset($images[0]) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
            "productOwnerName" => ($this->data->brand_name ? $this->data->brand_name : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data->brand, 'name')),
        ];
    }

    private function setPriceData()
    {
        $return = CatalogItem::checkPrice($this->data);
        $this->return['productPrice'] = $return['productPrice'];
        $this->return['productDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['productHasDiscount'] = $return['productHasDiscount'];
    }

    private function setMerchantData()
    {
        $brand = $this->data->brand;
        $this->return["productOwner"] = isset($brand->id) ? $brand->id : NULL;
        $this->return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, 'name');
        $this->return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($brand, 'store_slug', $this->return["productOwnerName"]);
    }

    private function setAddData()
    {
        $this->return['productWishlisted'] = '0';
        $this->return['productLiked'] = '0';
        $this->return['productShared'] = '0';
        $this->return['productInCart'] = '0';

        //wishlist
        if (\OlaHub\UserPortal\Models\WishList::where('item_id', $this->data->id)->count() > 0) {
            $this->return['productWishlisted'] = '1';
        }

        //like
        $post = \OlaHub\UserPortal\Models\Post::where('item_slug', $this->data->item_slug)->where('type', 'item_post')->first();
        if ($post && isset($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
            $this->return['productLiked'] = '1';
        }

        //share
        if ($post && isset($post->shares) && in_array(app('session')->get('tempID'), $post->shares)) {
            $this->return['productShared'] = '1';
        }


        //Cart
        $itemID = $this->data->id;
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use ($itemID) {
            $q->where('item_id', $itemID);
        })->count() > 0) {
            $this->return['productInCart'] = '1';
        }
    }

    private function setDefImageData()
    {
        $images = $this->data->images;
        if ($images->count() > 0) {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    private function setWishlistData()
    {
        $this->return['productWishlist'] = false;

        //wishlist
        $wishlist = \OlaHub\UserPortal\Models\WishList::where('item_id', $this->data->id)
            ->where('user_id', app('session')->get('tempID'))->first();
        if ($wishlist) {
            $this->return['productWishlist'] = true;
        }
    }
}
