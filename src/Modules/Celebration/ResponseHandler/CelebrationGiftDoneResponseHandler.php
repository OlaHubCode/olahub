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
                break;
            case "designer":
                $this->item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $this->data->item_id)->first();
                if ($this->item) {
                    $this->return = [
                        "celebrationGiftId" => isset($this->data->id) ? $this->data->id : 0,
                        "celebrationGiftType" => "designer",
                        "celebrationItem" => isset($this->item->id) ? $this->item->id : 0,
                        "celebrationItemName" => $this->item->name,
                        "celebrationItemSlug" => isset($this->item->item_slug) ? $this->item->item_slug : NULL,
                        'celebrationItemPrice' => number_format($this->data->country_paid, 2),
                        'celebrationItemQuantity' => number_format($this->data->quantity, 2),
                        'celebrationItemTotalPrice' =>  number_format($this->data->user_paid, 2),
                        'celebrationItemDiscountedPrice' => number_format($this->data->item_price, 2),
                        'celebrationItemHasDiscount' => $this->data->item_original_price == $this->data->item_price ? false : true
                    ];
                }
                break;
        }
        $this->setDefImageData();
    }

    private function setDefImageData()
    {
        $images = @$this->item->images;
        if (@$images->count() > 0) {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            $this->return['celebrationItemImages'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }
}
