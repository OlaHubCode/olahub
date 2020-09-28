<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\DesignerItems;
use League\Fractal;
use Illuminate\Http\Request;

class DesginerItemsHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $request;

    public function transform(DesignerItems $data)
    {
        $this->request = Request::capture();
        $this->data = $data;
        $this->setDefaultData();
        $this->setCartData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $itemPrice = DesignerItems::checkPrice($this->data);
        $this->return = [
            "type" => "designer",
            "productID" => isset($this->data->id) ? $this->data->id : 0,
            "productSlug" => isset($this->data->item_slug) ? $this->data->item_slug : null,
            "productRealID" => isset($this->data->id) ? $this->data->id : 0,
            "productName" => isset($this->data->name) ? $this->data->name : null,
            "productDescription" => isset($this->data->description) ? $this->data->description : null,
            "productInStock" => isset($this->data->item_stock) ? $this->data->item_stock : 0,
            "productPrice" => isset($this->data->price) ? \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->price, true) : 0,
            "number" => isset($this->data->_id) ? $this->data->_id : 0,
            "productShowLabel" => true
        ];
        $this->setDesignerData();
        $this->setPriceData();
        $this->setImageData();
        $this->setRateData();
        $this->setShippingDatesData();
        $this->setFollowStatus();
    }

    private function setDesignerData()
    {
        $designer = $this->data->designer;
        $this->return["productOwner"] = isset($designer->id) ? $designer->id : NULL;
        $this->return["productOwnerName"] = isset($designer->brand_name) ? $designer->brand_name : NULL;
        $this->return["productOwnerSlug"] = isset($designer->designer_slug) ? $designer->designer_slug : NULL;
        $this->return["productOwnerFollowers"] = isset($designer->id) ? \OlaHub\UserPortal\Models\Following::where("target_id", $designer->id)->where("type", 2)->count() : 0;
    }

    private function setPriceData()
    {
        if ($this->data->discounted_price_end_date && $this->data->discounted_price_end_date >= date("Y-m-d")) {
            $this->return["productOriginalPrice"] = $this->data->price ? \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->price, true) : 0;
            $this->return["productWillSavePerc"] = ceil(((\OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->price, false) - \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->discounted_price, false)) / \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->price, false)) * 100);
            $this->return["productWillSaveMount"] = ((\OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->price, false) - \OlaHub\UserPortal\Helpers\CommonHelper::setDesignerPrice($this->data->discounted_price, false))) . " JOD";
        }
    }

    private function setImageData()
    {
        $this->return["productImages"] = [];
        if ($this->data->images) {
            foreach ($this->data->images as $image) {
                $this->return["productImages"][] = \OlaHub\UserPortal\Helpers\CommonHelper::setContentUrl($image->content_ref);
            }
        } else {
            $this->return["productImages"][] = "/img/no_image.png";
        }

        $this->return["productImage"] = isset($this->return["productImages"][0]) ? $this->return["productImages"][0] : "/img/no_image.png";
    }

    private function setRateData()
    {
        $this->return["productRate"] = 0;
    }

    private function setShippingDatesData()
    {
        $dateFrom = \OlaHub\UserPortal\Helpers\CommonHelper::checkHolidaysDatesNumber($this->data->min_shipping_days);
        $dateTo = \OlaHub\UserPortal\Helpers\CommonHelper::checkHolidaysDatesNumber($this->data->max_shipping_days);
        $this->return["shippingDateFrom"] = date("D d F, Y", strtotime("+$dateFrom Days"));
        if ($dateTo) {
            $this->return["shippingDateTo"] = date("D d F, Y", strtotime("+$dateTo Days"));
        }
    }

    private function setCartData()
    {
        $this->return['productInCart'] = 0;
        $itemID = $this->data->id;
        if (app('session')->get('tempID')) {
            $headerCelebration = $this->request->headers->get("celebration") ? $this->request->headers->get("celebration") : "";
            if ($headerCelebration && $headerCelebration > 0) {
                // $this->checkCelebrationCart($headerCelebration, $itemID);
            } else {
                $this->checkDefaultCart($itemID);
            }
        } else {
            $this->checkNotLogeedCart();
        }
    }

    private function checkDefaultCart($itemID)
    {
        $cartItem = \OlaHub\UserPortal\Models\Cart::whereNull("calendar_id")->whereHas('cartDetails', function ($q) use ($itemID) {
            $q->where('item_id', $itemID);
            $q->where("item_type", "designer");
        })->count();
        if ($cartItem > 0) {
            $this->return['productInCart'] = 1;
        }
    }

    private function checkNotLogeedCart()
    {
        $cartCookie = $this->request->headers->get("cartCookie") ? json_decode($this->request->headers->get("cartCookie")) : [];
        if ($cartCookie && is_array($cartCookie) && count($cartCookie) > 0) {
            $id = $this->data->_id;
            foreach ($cartCookie as $item) {
                if ($id == $item->productId) {
                    $this->return['productInCart'] = 1;
                    return;
                }
            }
        }
    }

    private function setFollowStatus()
    {
        $d = \OlaHub\UserPortal\Models\Following::where("target_id", $this->data->designer_id)->where("type", 2)
            ->where('user_id', app('session')->get('tempID'))->first();
        $this->return['followed'] = $d ? true : false;
    }
}
