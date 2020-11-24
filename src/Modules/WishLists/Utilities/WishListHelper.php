<?php

namespace OlaHub\UserPortal\Helpers;

class WishListHelper extends OlaHubCommonHelper
{

    private $return;
    private $data;
    private $item;

    public function getWishListData($wishLists)
    {
        $response = [];
        foreach ($wishLists as $wishList) {
            $this->data = $wishList;
            $this->setDefaultData();
            $this->prepareItemData();
            $response[] = $this->return;
        }
        $final['data'] = $response;
        return $final;
    }

    private function prepareItemData()
    {
        if ($this->data->item_type == 'store') {
            $this->item = $this->data->itemsMainData;
            if ($this->item) {
                $this->setPriceData();
                $this->setItemOwnerData();
            } else {
                $this->data->delete();
                return;
            }
        } else {
            $this->item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $this->data->item_id)->first();
            $this->setDesignerItemOwnerData();
            $this->getDesignerItemPrice();
        }
        $this->setItemMainData();
        $this->setItemImageData();
        $this->setAddData($this->data->item_type);
    }

    private function setDefaultData()
    {
        $occassion = \OlaHub\UserPortal\Models\Occasion::where("id", $this->data->occasion_id)->first();
        $this->return["occasionName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occassion, "name");
        $this->return["occasionSlug"] = isset($occassion->occasion_slug) ? $occassion->occasion_slug : false;
        $this->return["occasionImage"] = isset($occassion->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occassion->logo_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
    }

    private function setItemMainData()
    {
        $itemName = isset($this->item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL;
        $itemDescription = isset($this->item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'description') : NULL;
        $stock = $this->data->item_type == 'store' ? \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item) : $this->item->item_stock;
        $this->return["productID"] = isset($this->item->id) ? $this->item->id : 0;
        $this->return["productSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->item, 'item_slug', $itemName);
        $this->return["productName"] = $itemName;
        $this->return["productType"] = $this->data->item_type;
        $this->return["productDescription"] = str_limit(strip_tags($itemDescription), 350, '.....');
        $this->return["productInStock"] = $stock;
    }

    private function setItemImageData()
    {
        $images = isset($this->item->images) ? $this->item->images : [];
        if (count($images)) {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['productImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData()
    {
        $this->return["productPrice"] = isset($this->item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->price) : 0;
        $this->return["productDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return["productHasDiscount"] = false;
        if (isset($this->item->has_discount) && $this->item->has_discount && strtotime($this->item->discounted_price_start_date) <= time() && strtotime($this->item->discounted_price_end_date) >= time()) {
            $this->return["productDiscountedPrice"] = isset($this->item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->item->discounted_price) : 0;
            $this->return["productHasDiscount"] = true;
        }
    }

    private function setItemOwnerData()
    {
        $brand = $this->item->brand;
        $this->return["productOwner"] = isset($brand->id) ? $brand->id : NULL;
        $this->return["productOwnerName"] = $brand->name;
        $this->return["productOwnerSlug"] = @$brand->store_slug;
    }

    private function setAddData($type)
    {
        $this->return['productWishlisted'] = 0;
        $this->return['productLiked'] = 0;
        $this->return['productInCart'] = 0;
        $itemID = $this->item->id;
        if (\OlaHub\UserPortal\Models\Cart::whereHas('cartDetails', function ($q) use ($itemID, $type) {
            $q->where('item_id', $itemID);
            $q->where('item_type', $type);
        })->count() > 0) {
            $this->return['productInCart'] = '1';
        }
    }
    
    private function getDesignerItemPrice()
    {
        $itemPrice = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($this->item);
        $this->return["productPrice"] = $itemPrice['productPrice'];
        $this->return["productDiscountedPrice"] = $itemPrice['productDiscountedPrice'];
        $this->return["productHasDiscount"] = $itemPrice['productHasDiscount'];
    }

    private function setDesignerItemOwnerData()
    {
        $designer = $this->item->designer;
        $this->return["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $this->return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, 'brand_name');
        $this->return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($designer, 'designer_slug', $this->return["productOwnerName"]);
    }
}
