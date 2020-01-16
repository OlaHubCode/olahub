<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\DesginerItems;
use League\Fractal;

class DesginerItemsSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(DesginerItems $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setPriceData($this->data);
        $this->setDefImageData($this->data);
        return $this->return;
    }

    private function setDefaultData() {
        $this->return = [
            "itemName" => isset($this->data->item_title) ? $this->data->item_title : NULL,
            "itemDescription" => isset($this->data->item_description) ? $this->data->item_description : NULL,
            "itemSlug" => isset($this->data->item_slug) ? $this->data->item_slug : NULL,
            "itemType" => 'desginer_items',
            "brand" => isset($this->data->designer_name) ? $this->data->designer_name : NULL,
        ];

    }
    private function setDefImageData($item) {
        $images = [];
        if(isset($item->item_images)){
            $images = $item->item_images;
        }elseif (isset($item->item_image)) {
            $images = $item->item_image;
        }
        if ($images && count($images) > 0) {
            $this->return['itemImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]);
        } else {
            $this->return['itemImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setPriceData($product) {
        $this->return["itemPrice"] = isset($product->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->item_price) : 0;
        $this->return["itemDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return["itemHasDiscount"] = false;
        if (isset($product->discount_end_date) && $product->discount_end_date && $product->discount_end_date >= date("Y-m-d")) {
            $this->return["itemPrice"] = isset($product->item_original_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->item_original_price) : 0;
            $this->return["itemDiscountedPrice"] = isset($product->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->item_price) : 0;
            $this->return["itemHasDiscount"] = true;
        }
    }

}
