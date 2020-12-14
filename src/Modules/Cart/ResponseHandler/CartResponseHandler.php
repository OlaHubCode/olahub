<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Cart;
use League\Fractal;

class CartResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $items;
    private $minShipDate = 0;
    private $maxShipDate = 0;

    public function transform(Cart $data)
    {
        $this->data = $data;
        $this->items = $this->data->cartDetails;
        $this->setDefaultData();
        $this->setItemMainData();
        $this->setShippingDatesData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $country = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->find($this->data->country_id);
        if ($this->data->shipped_to) {
            $country2 = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->find($this->data->shipped_to);
        }
        $change_country = \OlaHub\UserPortal\Models\CartItems::where("shopping_cart_id", $this->data->id)->where("item_type", "store")->count() > 0 ? false : true;
        $this->return = [
            "cartID" => isset($this->data->id) ? $this->data->id : 0,
            "cartIsGift" => isset($this->data->for_friend) && $this->data->for_friend > 0 ? true : false,
            "cartCountry" => isset($this->data->country_id) && $this->data->country_id > 0 ? $this->data->country_id : false,
            "cartCountryName" => isset($country->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, "name") : false,
            "cartCountryToName" => isset($this->data->shipped_to) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country2, "name") : false,
            "cartCountryCode" => isset($country->two_letter_iso_code) ? $country->two_letter_iso_code : NULL,
            "cartEnableCountry" => $change_country,
            "cartDate" => isset($this->data->shopping_cart_date) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDateTime($this->data->shopping_cart_date) : 0,
        ];
    }

    private function setItemMainData()
    {
        $this->return['products'] = [];
        if ($this->items) {
            foreach ($this->items as $cartItem) {
                switch ($cartItem->item_type) {
                    case "store":
                        $item = \OlaHub\UserPortal\Models\CatalogItem::where("id", $cartItem->item_id)->first();
                        if ($item) {
                            $this->getStoreItem($item, $cartItem);
                        }
                        break;
                    case "designer":
                        $item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $cartItem->item_id)->first();
                        if ($item) {
                            $this->getDesignerItem($item, $cartItem);
                        }
                        break;
                }
            }
        }
    }

    private function getDesignerItem($item, $cartItem)
    {
        $itemPrice = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item);
        $itemOwner = $this->setDesignerItemOwnerData($item);
        $this->return['products'][] = array(
            "productID" => isset($item->id) ? $item->id : 0,
            "productValue" => isset($item->id) ? $item->id : 0,
            "productType" => "designer",
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name')),
            "productName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name'),
            "productDescription" => str_limit(strip_tags(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description')), 350, '.....'),
            "productInStock" => isset($item->item_stock) && $item->item_stock ? $item->item_stock : "1",
            "productPrice" => $itemPrice['productPrice'],
            "productDiscountedPrice" => $itemPrice['productDiscountedPrice'],
            "productHasDiscount" => $itemPrice['productHasDiscount'],
            "productQuantity" => $cartItem->quantity,
            "productTotalPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((float) \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item, true, false) * $cartItem->quantity),
            "productImage" => $this->setItemImageData($item),
            "productOwner" => $itemOwner['productOwner'],
            "productOwnerName" => $itemOwner['productOwnerName'],
            "productOwnerSlug" => $itemOwner['productOwnerSlug'],
            "productselectedAttributes" => $this->setDesignerItemSelectedAttrData($item),
            "productCustomeItem" => $this->setItemCustomData($cartItem->customize_data),
        );
        if ($this->minShipDate == 0 || ($item->min_shipping_days > 0 && $item->min_shipping_days < $this->minShipDate)) {
            $this->minShipDate = $item->min_shipping_days;
        }
        if ($this->maxShipDate == 0 || ($item->max_shipping_days > 0 && $item->max_shipping_days > $this->maxShipDate)) {
            $this->maxShipDate = $item->max_shipping_days;
        }
    }

    private function setDesignerItemSelectedAttrData($oneItem)
    {
        $return = [];
        if (isset($oneItem->item_attr) && is_array($oneItem->item_attr) && count($oneItem->item_attr)) {
            $valuesData = \OlaHub\UserPortal\Models\AttrValue::whereIn("id", $oneItem->item_attr)->get();
            foreach ($valuesData as $valueMain) {
                $attribute = $valueMain->attributeMainData;
                $return[$valueMain->product_attribute_id] = [
                    'val' => $valueMain->id,
                    'label' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($valueMain, 'attribute_value'),
                    "valueName" => isset($attribute->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($attribute, 'name') : NULL,
                ];
            }
        }
        return $return;
    }

    private function setDesignerItemOwnerData($item)
    {
        $designer = \OlaHub\UserPortal\Models\Designer::find($item->designer_id);
        $return["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, 'brand_name');
        $return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($designer, 'designer_slug', $return["productOwnerName"]);

        return $return;
    }

    private function setShippingDatesData()
    {
        if ($this->minShipDate > 0) {
            $isVocher = true;
            foreach ($this->items as $item) {

                switch ($item->item_type) {
                    case "store":

                        $item = \OlaHub\UserPortal\Models\CatalogItem::where("id", $item->item_id)->first();
                        if ($item) {

                            if (!$item->is_voucher) {
                                $isVocher = false;
                            }
                        }
                        break;
                    case "designer":
                        $isVocher = false;
                        break;
                }
            }
            if ($isVocher)
                $dateFrom = 0;
            else
                $dateFrom = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkHolidaysDatesNumber($this->minShipDate);
            if (app('session')->get('def_lang')->default_locale == 'ar') {
                $date = ARABIC_DAYS[date("w", strtotime("+$dateFrom Days")) - 1] . " " .
                    date("d", strtotime("+$dateFrom Days")) . " " .
                    ARABIC_MONTHS[date("n", strtotime("+$dateFrom Days")) - 1] . "، " . date("Y", strtotime("+$dateFrom Days"));
            } else {
                $date = date("D d F, Y", strtotime("+$dateFrom Days"));
            }
            $this->return["shippingDateFrom"] = $date;
            $this->return["dateFrom"] = date("Y-m-d", strtotime("+$dateFrom Days"));
        }

        if ($this->maxShipDate > 0) {
            $dateTo = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkHolidaysDatesNumber($this->maxShipDate);
            if ($dateFrom != $dateTo) {
                if (app('session')->get('def_lang')->default_locale == 'ar') {
                    $date = ARABIC_DAYS[date("w", strtotime("+$dateTo Days")) - 1] . " " .
                        date("d", strtotime("+$dateTo Days")) . " " .
                        ARABIC_MONTHS[date("n", strtotime("+$dateTo Days")) - 1] . ", " . date("Y", strtotime("+$dateTo Days"));
                } else {
                    $date = date("D d F, Y", strtotime("+$dateTo Days"));
                }
                $this->return["shippingDateTo"] = $date;
                $this->return["dateTo"] = date("Y-m-d", strtotime("+$dateTo Days"));
            }
        }
    }

    private function getStoreItem($item, $cartItem)
    {
        $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item);
        $itemOwner = $this->setItemOwnerData($item);
        $country = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->find($item['country_id']);
        $this->return['products'][] = array(
            "productID" => isset($item->id) ? $item->id : 0,
            "productType" => "store",
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name')),
            "productName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name'),
            "productDescription" => str_limit(strip_tags(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description')), 350, '.....'),
            "productInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($item),
            "productPrice" => $itemPrice['productPrice'],
            "productDiscountedPrice" => $itemPrice["productHasDiscount"] ? $itemPrice['productDiscountedPrice'] : $itemPrice["productPrice"],
            "productHasDiscount" => $itemPrice['productHasDiscount'],
            "productQuantity" => $cartItem->quantity,
            "isVoucher" =>     $item['is_voucher'],
            "productTotalPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((float) \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item, true, false) * $cartItem->quantity),
            "productImage" => $this->setItemImageData($item),
            "productOwner" => $itemOwner['productOwner'],
            "productOwnerName" => $itemOwner['productOwnerName'],
            "productOwnerSlug" => $itemOwner['productOwnerSlug'],
            "productselectedAttributes" => $this->setItemSelectedAttrData($item),
            "productCustomeItem" => $this->setItemCustomData($cartItem->customize_data),
            "countryCode" => $country->two_letter_iso_code,
            "countryName" => json_decode($country->name),
        );

        if ($this->minShipDate == 0 || ($item->estimated_shipping_time > 0 && $item->estimated_shipping_time < $this->minShipDate)) {
            $this->minShipDate = $item->estimated_shipping_time;
        }
        if ($this->maxShipDate == 0 || ($item->max_shipping_days > 0 && $item->max_shipping_days > $this->maxShipDate)) {
            $this->maxShipDate = $item->max_shipping_days;
        }
    }

    private function setItemImageData($item)
    {
        $images = isset($item->images) ? $item->images : [];
        if (count($images) > 0 && $images->count() > 0) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setItemSelectedAttrData($item)
    {
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

    private function setItemOwnerData($item)
    {
        $merchant = $item->merchant;
        $return["productOwner"] = isset($merchant->id) ? $merchant->id : NULL;
        $return["productOwnerName"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($merchant, 'company_legal_name');
        $return["productOwnerSlug"] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($merchant, 'merchant_slug', $return["productOwnerName"]);

        return $return;
    }

    private function setItemCustomData($item)
    {
        $return = [];
        if ($item != null) {
            $customItem = unserialize($item);
            $return["productCustomImage"] = isset($customItem['image']) ? $customItem['image'] : '';
            $return["productCustomeText"] = isset($customItem['text']) ? $customItem['text'] : '';
        }

        return $return;
    }
}
