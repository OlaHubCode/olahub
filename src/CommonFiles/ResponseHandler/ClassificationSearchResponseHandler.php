<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CatalogItem;
use League\Fractal;

class ClassificationSearchResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CatalogItem $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $storeData = \OlaHub\UserPortal\Models\Brand::where('id',$this->data->store_id)->first();
        $itemName = isset($this->data->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name') : NULL;
        $itemDescription = isset($this->data->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'description') : NULL;
        $itemImage = $this->setDefImageData($this->data);
        $itemPrice = CatalogItem::checkPrice($this->data);
        $this->return = [
            "itemName" => $itemName,
            "itemDescription" => $itemDescription,
            "itemPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->price),
            "itemDiscountedPrice" => $itemPrice['productDiscountedPrice'],
            "itemHasDiscount" => $itemPrice['productHasDiscount'],
            "itemImage" => $itemImage,
            "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($this->data, 'item_slug', $itemName),
            "itemType" => 'classification',
            "itemClassName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->data, 'name'),
            "brand" => $storeData->name,
        ];

    }

    private function setDefImageData($item) {
        $images = $item->images;
        if ($images->count() > 0) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

}
