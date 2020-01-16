<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CatalogItem;
use League\Fractal;

class NotLoginCartResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(CatalogItem $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $item = $this->data;
        $itemName = isset($item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') : NULL;
        $itemDescription = isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description') : NULL;
        $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item);
        $itemImage = $this->setItemImageData($item);
        $itemOwner = $this->setItemOwnerData($item);
        $itemAttrs = $this->setItemSelectedAttrData($item);
        //$productAttributes = $this->setAttrData($item);
        $itemFinal = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item,true,false);
        $country = \OlaHub\UserPortal\Models\Country::find($item->country_id);
        $currency = $country->currencyData;
        $this->return = [
            "productID" => isset($item->id) ? $item->id : 0,
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
            "productName" => $itemName,
            "productDescription" => str_limit(strip_tags($itemDescription), 350, '.....'),
            "productInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($item),
            "productPrice" => $itemPrice['productPrice'],
            "productDiscountedPrice" => $itemPrice['productDiscountedPrice'],
            "productHasDiscount" => $itemPrice['productHasDiscount'],
            "productQuantity" => 1,
            "productCurrency" => isset($currency) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency->code) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency("JOD"),
            "productTotalPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((double) $itemFinal, false),
            "productImage" => $itemImage,
            "productOwner" => $itemOwner['productOwner'],
            "productOwnerName" => $itemOwner['productOwnerName'],
            "productOwnerSlug" => $itemOwner['productOwnerSlug'],
            "productselectedAttributes" => $itemAttrs,
            //"productAttributes" => $productAttributes,
        ];
    }


    private function setItemImageData($item) {
        $images = isset($item->images) ? $item->images : [];
        if (count($images) > 0 && $images->count() > 0) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
    
     private function setItemSelectedAttrData($item) {
        $return = [];
        $values = $item->valuesData;
        if ($values->count() > 0) {
            foreach ($values as $itemValue) {
                $value = $itemValue->valueMainData;
                $parent = $value->attributeMainData;
                $return[$value->product_attribute_id] = [
                    'val' => $value->id,
                    'label' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($value, 'attribute_value'),
                    "valueName" => isset($parent->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($parent, 'name') : NULL,
                ];
            }
        }
        return $return;
    }

    private function setItemOwnerData($item) {
        $merchant = $item->merchant;
        $return["productOwner"] = isset($merchant->id) ? $merchant->id : NULL;
        $return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($merchant, 'company_legal_name');
        $return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($merchant, 'merchant_slug', $return["productOwnerName"]);
        
        return $return;
    }
    
    /*private function setAttrData($item) {
        if ($item->parent_item_id > 0) {
            $parentData = $item->templateItem;
        } else {
            $parentData = $item;
        }
        $values = \OlaHub\UserPortal\Models\ItemAttrValue::where('parent_item_id', $parentData->id)->get();
        $addedParnts = [];
        $attributes = [];
        foreach ($values as $itemValue) {
            $value = $itemValue->valueMainData;
            if (in_array($value->product_attribute_id, $addedParnts)) {
                $attributes[$value->product_attribute_id]['childsData'][] = [
                    "value" => isset($value->id) ? $value->id : 0,
                    "text" => isset($value->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($value, 'attribute_value') : NULL,
                ];
            } else {
                $parent = $value->attributeMainData;
                $attributes[$value->product_attribute_id] = [
                    "valueID" => isset($parent->id) ? $parent->id : 0,
                    "valueName" => isset($parent->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($parent, 'name') : NULL,
                ];
                $attributes[$value->product_attribute_id]['childsData'][] = [
                    "value" => isset($value->id) ? $value->id : 0,
                    "text" => isset($value->attribute_value) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($value, 'attribute_value') : NULL,
                ];
                $addedParnts[] = $value->product_attribute_id;
            }
        }
        return $attributes;
    }*/


}
