<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserBillDetails;
use League\Fractal;

class CelebrationGiftDoneResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $item;

    public function transform(UserBillDetails $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        switch ($this->data->item_type) {
            case "store":
                $this->item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope('country')->where('id', $this->data->item_id)->first();
                $this->return = [
                    "celebrationGiftId" => isset($this->data->id) ? $this->data->id : 0,
                    "celebrationGiftType" => "store",
                    "celebrationItem" => isset($this->item->id) ? $this->item->id : 0,
                    "celebrationItemName" => isset($this->item) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($this->item, 'name') : NULL,
                    "celebrationItemSlug" => isset($this->item->item_slug) ? $this->item->item_slug : NULL,
                    'celebrationItemPrice' => number_format($this->data->country_paid, 2),
                    'celebrationItemQuantity' => number_format($this->data->quantity, 2),
                    'celebrationItemTotalPrice' =>  number_format($this->data->user_paid, 2),
                    'celebrationItemDiscountedPrice' => number_format($this->data->item_price, 2),
                    'celebrationItemHasDiscount' => $this->data->item_original_price == $this->data->item_price ? false : true
                ];
                $this->setDefImageData();
                break;
            case "designer":
                $itemMain = \OlaHub\UserPortal\Models\DesginerItems::whereIn("item_ids", [$this->data->item_id])->first();
                if ($itemMain) {
                    $item = false;
                    if (isset($itemMain->items) && count($itemMain->items) > 0) {
                        foreach ($itemMain->items as $oneItem) {
                            if ($oneItem["item_id"] == $this->data->item_id) {
                                $item = (object) $oneItem;
                            }
                        }
                    }
                    if (!$item) {
                        $item = $itemMain;
                    }
                    $this->return = [
                        "celebrationGiftId" => isset($this->data->id) ? $this->data->id : 0,
                        "celebrationGiftType" => "designer",
                        "celebrationItem" => isset($item->item_id) ? $item->item_id : 0,
                        "celebrationItemName" => $itemMain->item_title,
                        "celebrationItemSlug" => isset($item->item_slug) ? $item->item_slug : NULL,
                        'celebrationItemPrice' => number_format($this->data->country_paid, 2),
                        'celebrationItemQuantity' => number_format($this->data->quantity, 2),
                        'celebrationItemTotalPrice' =>  number_format($this->data->user_paid, 2),
                        'celebrationItemDiscountedPrice' => number_format($this->data->item_price, 2),
                        'celebrationItemHasDiscount' => $this->data->item_original_price == $this->data->item_price ? false : true
                    ];
                    $this->setDesignerDefImageData($item);
                }
                break;
        }
    }

    private function setDefImageData()
    {
        $images = $this->item->images;
        if ($images->count() > 0) {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDesignerDefImageData($item)
    {
        $images = isset($item->item_image) ? $item->item_image : (isset($item->item_images) ? $item->item_images : false);
        if ($images && count($images) > 0) {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]);
        } else {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
}
