<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\DesignerItems;
use League\Fractal;

class DesignerItemsSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(DesignerItems $data) {
        $this->data = $data;
        $this->setDefaultData();
        $this->setPriceData($this->data);
        $this->setDefImageData();
        return $this->return;
    }

    private function setDefaultData() {
        $designer = $this->data->designer;
        $this->return = [
            "itemName" => isset($this->data->name) ? $this->data->name : NULL,
            "itemDescription" => isset($this->data->description) ? $this->data->description : NULL,
            "itemSlug" => isset($this->data->item_slug) ? $this->data->item_slug : NULL,
            "itemType" => 'desginer_items',
            "brand" => isset($designer->brand_name) ? $designer->brand_name : NULL,
        ];

    }
    private function setDefImageData() {
        $images = $this->data->images;
        if ($images) {
            $this->return['itemImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['itemImage'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
    private function setPriceData($product) {
        $this->return["itemPrice"] = isset($product->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->price) : 0;
        $this->return["itemDiscountedPrice"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0);
        $this->return["itemHasDiscount"] = false;
        if (isset($product->discounted_price_end_date) && $product->discounted_price_end_date && $product->discounted_price_end_date >= date("Y-m-d")) {
            $this->return["itemPrice"] = isset($product->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->price) : 0;
            $this->return["itemDiscountedPrice"] = isset($product->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($product->discounted_price) : 0;
            $this->return["itemHasDiscount"] = true;
        }
    }

}
