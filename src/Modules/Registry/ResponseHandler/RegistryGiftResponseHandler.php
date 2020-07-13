<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\CartItems;
use League\Fractal;
use OlaHub\UserPortal\Models\RegistryGiftModel;

class RegistryGiftResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $item;

    public function transform(RegistryGiftModel $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        $this->setGiftOwnerImageData();

        return $this->return;
    }

    private function setDefaultData()
    {
        switch ($this->data->item_type) {
            case "store":
                $this->item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope('country')->where('id', $this->data->item_id)->first();
                $this->return = [
                    "registryGiftId" => isset($this->data->id) ? $this->data->id : 0,
                    "registryGiftType" => "store",
                    "registryGiftOwner" => $this->data->created_by == app('session')->get('tempID') ? TRUE : FALSE,
                    "registryItem" => isset($this->item->id) ? $this->item->id : 0,
                    "registryItemName" => isset($this->item) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL,
                    "registryItemSlug" => isset($this->item->item_slug) ? $this->item->item_slug : NULL,
                    "registryItemSKU" => isset($this->item->sku) ? $this->item->sku : NULL,
                    "registryItemInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($this->item),
                ];
                $this->setPriceData();
                break;
            case "designer":
                $this->item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $this->data->item_id)->first();
                if ($this->item) {
                    $this->return = [
                        "registryGiftId" => isset($this->data->id) ? $this->data->id : 0,
                        "registryGiftType" => "designer",
                        "registryGiftOwner" => $this->data->created_by == app('session')->get('tempID') ? TRUE : FALSE,
                        "registryItem" => isset($this->item->id) ? $this->item->id : 0,
                        "registryItemName" => $this->item->name,
                        "registryItemSlug" => isset($this->item->item_slug) ? $this->item->item_slug : NULL,
                        "registryItemSKU" => isset($this->item->sku) ? $this->item->sku : NULL,
                        "registryItemInStock" => isset($this->item->item_stock) ? $this->item->item_stock : 1,
                    ];
                    $this->setDesignerPrice($this->item);
                }
                break;
        }
        $this->setDefImageData();
    }

    private function setDefImageData()
    {
        $images = @$this->item->images;
        if (@$images->count() > 0) {
            $this->return['registryItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['registryItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setGiftOwnerImageData()
    {
        $giftOwner = \OlaHub\UserPortal\Models\UserModel::where('id', $this->data->created_by)->first();
        $this->return["registryGiftOwnerName"] = isset($giftOwner) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($giftOwner, 'name') : NULL;
        if (isset($giftOwner->profile_picture)) {
            $this->return['registryGiftOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($giftOwner->profile_picture);
        } else {
            $this->return['registryGiftOwnerPhoto'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setPriceData()
    {
        $registry = \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->data->registry_id)->first();
        $return = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($this->item, false, true, $registry->country_id);
        $this->return['registryItemPrice'] = $return['productPrice'];
        $this->return['registryItemQuantity'] = $this->data->quantity;
        $this->return['registryItemTotalPrice'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->total_price, true, $registry->country_id);
        $this->return['registryItemDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['registryItemHasDiscount'] = $return['productHasDiscount'];
    }

    private function setDesignerPrice($item)
    {
        $registry = \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->data->registry_id)->first();
        $return = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item, false, true, $registry->country_id);
        $this->return['registryItemPrice'] = $return['productPrice'];
        $this->return['registryItemQuantity'] = isset($this->data->quantity) ? $this->data->quantity : 1;
        $this->return['registryItemTotalPrice'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->data->total_price, true, $registry->country_id);
        $this->return['registryItemDiscountedPrice'] = $return['productDiscountedPrice'];
        $this->return['registryItemHasDiscount'] = $return['productHasDiscount'];
    }

}
