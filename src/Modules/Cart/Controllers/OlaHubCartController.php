<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\Cart;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpFoundation\Cookie;

class OlaHubCartController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $cartModel;
    private $id;
    private $userId;
    private $celebration;
    private $cart;
    private $userMongo;
    private $friends;
    private $calendar;
    protected $userAgent;
    private $cartCookie;
    protected $uploadVideoData;
    private $countryId;
    private $lang;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = (object) $return['requestData'];
        $this->requestFilter = (object) $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->NotLoginCartItems = $return['requestData'];
        $this->id = isset($this->requestData->valueID) && $this->requestData->valueID > 0 ? $this->requestData->valueID : false;
        $this->userId = app('session')->get('tempID') > 0 ? app('session')->get('tempID') : false;
        $this->celebration = false;
        $this->calendar = false;
        $this->cart = false;
        $this->uploadVideoData = $request->all();
        $req = Request::capture();
        $this->cartCookie = $req->headers->get("cartCookie") ? json_decode($req->headers->get("cartCookie")) : [];
        $this->countryId = $request->header('country');
        $this->lang = $request->header('language');
    }

    public function getList($type = "default", $first = false)
    {
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $return = $this->handleCartReturn($first);
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function uploadGiftVideo(Request $request)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Cart", 'function_name' => "Upload cart gift video"]);
        $this->requestData = isset($this->uploadVideoData) ? $this->uploadVideoData : [];
        $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData["GiftVideo"], DEFAULT_IMAGES_PATH . "cart/", STORAGE_URL . '/cart', false);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Checking if array key exists for upload cart gift video", "action_startData" => $uploadResult]);
        if (array_key_exists('path', $uploadResult)) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'Gift Video Uploaded', 'code' => 200]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => true, 'msg' => 'WishVideoSuccussfully', 'video' => $uploadResult['path'], 'code' => 200], 200);
        } else {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $uploadResult]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            response($uploadResult, 200);
        }
    }

    public function setNewCountryForDefaultCart($country)
    {
        $type = "default";
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $countryData = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->find($country);
        if (!$countryData) {
            throw new NotAcceptableHttpException(404);
        }
        $this->checkCart($type);
        $this->cart->country_id = $country;
        $this->cart->save();
        $return["status"] = true;
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function setDefaultCartToBeGift($type = "default")
    {
        $return = ["status" => false, "msg" => "noData"];
        if (isset($this->requestData->user) && $this->requestData->user > 0) {
            $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->where("id", $this->requestData->user)->first();
            if ($user) {
                $checkPermission = $this->checkActionPermission($type);
                if (isset($checkPermission['status']) && !$checkPermission['status']) {
                    return response($checkPermission, 200);
                }
                $this->checkCart($type);
                $this->cart->for_friend = $user->id;
                $this->cart->save();
                $return = ["status" => true, "msg" => "friendSelected"];
            }
        }

        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function setCartToBeGiftDate($type = "default")
    {
        $return = ["status" => false, "msg" => "noData"];
        if (isset($this->requestData->date) && $this->requestData->date) {
            $checkPermission = $this->checkActionPermission($type);
            if (isset($checkPermission['status']) && !$checkPermission['status']) {
                return response($checkPermission, 200);
            }
            $this->checkCart($type);
            $date = date("Y-m-d", strtotime($this->requestData->date));
            $this->cart->gift_date = $date;
            $this->cart->save();
            $return = ["status" => true, "msg" => "friendSelected"];
        }

        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function cancelDefaultCartToBeGift($type = "default")
    {
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $this->cart->for_friend = null;
        $this->cart->gift_date = null;
        $this->cart->save();
        $return = ["status" => true, "msg" => "friendSelected"];
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function getDefaultCartGiftDetails($type = "default")
    {
        $return = ["status" => false, "msg" => "noData"];
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        if ($this->cart->for_friend > 0) {
            $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->where("id", $this->cart->for_friend)->first();
            if ($user) {
                $oldTimeStamp = strtotime($this->cart->gift_date . " 00:00:00");
                $currentDate = date("Y-m-d", strtotime("+2 days"));
                $currentTimeStamp = strtotime($currentDate . " 00:00:00");
                if ($currentTimeStamp > $oldTimeStamp) {
                    $this->cart->gift_date = null;
                    $this->cart->save();
                }

                $userName = $user->first_name . " " . $user->last_name;
                $userSlug = $user->profile_url;
                $giftDate = $this->cart->gift_date ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::convertStringToDate($this->cart->gift_date) : false;
                $giftData = [
                    "userName" => $userName,
                    "userSlug" => $userSlug,
                    "giftDate" => $giftDate,
                ];
                $return = ["status" => true, "msg" => "data fetched", "data" => $giftData];
            }
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getListCart', $this->userAgent);
        return response($return, 200);
    }

    public function getCartTotals($type = "default")
    {

        if (!$this->userId) {
            if ($this->cartCookie && is_array($this->cartCookie) && count($this->cartCookie) > 0) {
                $return['data'] = (new \OlaHub\UserPortal\Helpers\CartHelper)->setNotLoggedCartTotal($this->cartCookie);
                $return["status"] = true;
                return response($return, 200);
            }
        }

        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->cart, '\OlaHub\UserPortal\ResponseHandlers\CartTotalsResponseHandler');
        $return["status"] = true;
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->cart, $return, 'getCartTotals', $this->userAgent);
        return response($return, 200);
    }

    public function newCartItem($itemType = "store", $type = "default")
    {
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Cart::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $created = $this->cartAction($itemType);
        if ($created) {
            $return = [];
            $return['status'] = TRUE;
            $return['code'] = 200;
        } else {
            throw new NotAcceptableHttpException(404);
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, $return, 'newCartItem', $this->userAgent);
        return response($return, 200);
    }

    public function removeCartItem($itemType = "store", $type = "default")
    {
        $return = ['status' => FALSE, 'msg' => 'ProductNotCart', 'code' => 204];
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Cart::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $data = false;
        $data = $this->cart->cartDetails()->where('item_id', $this->requestData->itemID)->where("item_type", $itemType)->first();
        if ($data) {
            if ($this->celebration) {
                if ($this->celebration->commit_date || ($data->created_by != app('session')->get('tempID') && $this->celebration->created_by != app('session')->get('tempID'))) {
                    return response(['status' => false, 'msg' => 'NotAllowToDeleteThisGift', 'code' => 400], 200);
                }
            }
            $data->delete();
            $totalPrice = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
            $this->cart->total_price = $totalPrice;
            $this->cart->save();
            $this->handleRemoveItemFromCelebration($totalPrice);
            $return = [];
            $return['status'] = TRUE;
            $return['code'] = 200;
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, $return, 'removeCartItem', $this->userAgent);
        return response($return, 200);
    }

    public function getNotLoginCartItems()
    {
        if (isset($this->NotLoginCartItems["products"]) && count($this->NotLoginCartItems["products"]) > 0) {
            $return['data'] = [];
            $storeItems = [];
            $designerItems = [];
            foreach ($this->NotLoginCartItems["products"] as $oneItem) {
                if (isset($oneItem["id"]) && $oneItem["id"] > 0) {
                    if (isset($oneItem["type"]) && $oneItem["type"] == "designer") {
                        $designerItems[] = $oneItem["id"];
                    } else {
                        $storeItems[] = $oneItem["id"];
                    }
                }
            }
            if (count($designerItems) > 0) {
                foreach ($designerItems as $designerItem) {
                    $itemData = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_ids', [$designerItem])->first();
                    $return["data"][] = $this->getDesignerItemData($itemData, $designerItem);
                }
            }
            if (count($storeItems) > 0) {
                foreach ($storeItems as $storeItem) {
                    $itemData = \OlaHub\UserPortal\Models\CatalogItem::where('id', $storeItem)->first();
                    $inStock = \OlaHub\UserPortal\Models\CatalogItem::checkStock($itemData);
                    if ($inStock > 0) {
                        $return["data"][] = $this->handleStoreItemsResponse($itemData);
                    }
                }
            }

            $return['status'] = true;
            $return['code'] = 200;
            return response($return, 200);
        }
        throw new NotAcceptableHttpException(404);
    }

    /*
     * Helper functions for this module
     */

    private function checkActionPermission($type)
    {
        if (!$this->userId) {
            throw new NotAcceptableHttpException(404);
        }
        if (in_array($type, ["event", "celebration"]) && !$this->id) {
            throw new UnauthorizedHttpException(401);
        }
        if ($type == "event" && $this->id > 0 && $this->userId > 0) {
            $this->userMongo = \OlaHub\UserPortal\Models\UserMongo::where("user_id", $this->userId)->first();
            $this->friends = $this->userMongo->friends;
            if (!is_array($this->friends) || count($this->friends) <= 0) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
            $time = strtotime("+3 Days");
            $minTime = date("Y-m-d", $time);
            $this->calendar = \OlaHub\UserPortal\Models\CalendarModel::whereIn("user_id", $this->friends)
                ->where("id", $this->id)
                ->where("calender_date", ">=", $minTime)
                ->first();
            if (!$this->calendar) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
        }
        if ($type == "celebration" && $this->id > 0 && $this->userId > 0) {
            $userId = $this->userId;
            $this->celebration = \OlaHub\UserPortal\Models\CelebrationModel::whereHas("celebrationParticipants", function ($q) use ($userId) {
                $q->where("user_id", $userId);
            })->where("id", $this->id)->first();
            if (!$this->celebration) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
        }
    }

    private function handleCartReturn($first = false)
    {
        if ($this->celebration) {
            $shippingFees = $this->cart->cartDetails()->whereHas('itemsMainData', function ($q) {
                $q->where('is_shipment_free', '1');
            })->first() ? SHIPPING_FEES : 0;
            $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $this->cart->id)->orderBy('paricipant_likers', 'desc')->get();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($cartDetails, '\OlaHub\UserPortal\ResponseHandlers\CelebrationGiftResponseHandler');
            $return['total'] = $this->cart->total_price > 0 ? array(
                'sub_price' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->cart->total_price, true, $this->celebration->country_id),
                'shipping_fees' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($shippingFees, true, $this->celebration->country_id),
                'total_price' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->cart->total_price + $shippingFees, true, $this->celebration->country_id),
            ) : null;
        } else {
            if ($first) {
                $countryData = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->countryId)->first();
                $this->cart->for_friend = null;
                $this->cart->gift_date = null;
                $this->cart->country_id = $countryData->id;
                $this->cart->save();
            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->cart, '\OlaHub\UserPortal\ResponseHandlers\CartResponseHandler');
        }

        $return['status'] = TRUE;
        $return['code'] = 200;
        return $return;
    }

    protected function cartFilter($type)
    {
        $this->cartModel = (new Cart)->newQuery();
        switch ($type) {
            case "default":
                return $this->cartModel->whereNull("calendar_id")->first();
            case "celebration":
                return $this->cartModel->withoutGlobalScope("countryUser")
                    ->whereNull("calendar_id")
                    ->whereNull("user_id")
                    ->where("celebration_id", $this->id)->first();
            case "event":
                return $this->cartModel->where("calendar_id", $this->id)->first();
        }
    }

    private function checkCart($type)
    {
        $this->cart = null;
        $checkCart = $this->cartFilter($type);
        $countryData = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->countryId)->first();
        if ($checkCart) {
            $this->cart = $checkCart;
            if ($this->cart->country_id == 0) {
                $this->cart->country_id = $countryData->id;
                // $this->cart->country_id = 5;
                $this->cart->save();
            }
            (new \OlaHub\UserPortal\Helpers\CartHelper)->checkOutOfStockInCartItem($checkCart->id, $this->celebration);
        }

        if (!$this->cart) {
            if ($this->creatCart($type)) {
                $this->cart = $this->cartFilter($type);
                if ($this->cart->country_id == 0) {
                    $this->cart->country_id = $countryData->id;
                    // $this->cart->country_id = 5;
                    $this->cart->save();
                }
            } else {
                throw new NotAcceptableHttpException(404);
            }
        }
    }

    private function creatCart($type)
    {
        $country = $this->celebration ? \OlaHub\UserPortal\Models\Country::where('id', $this->celebration->country_id)->first() : app('session')->get('def_country');
        $this->cartModel = new Cart;
        $this->cartModel->shopping_cart_date = date('Y-m-d h:i');
        $this->cartModel->total_price = '0.00';
        $this->cartModel->currency_id = $country->currency_id;
        $this->cartModel->country_id = $country->id;
        switch ($type) {
            case "default":
                return $this->cartModel->save();
            case "celebration":
                $this->cartModel->celebration_id = $this->id;
                return $this->cartModel->save();
            case "event":
                $this->cartModel->calendar_id = $this->id;
                return $this->cartModel->save();
        }
    }

    private function cartAction($itemType = "store")
    {
        $country = $this->celebration ? $this->celebration->country_id : app('session')->get('def_country')->id;
        $likers['user_id'] = [];
        switch ($itemType) {
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")->whereHas('merchant', function ($q) use ($country) {
                    $q->withoutGlobalScope("country");
                    $q->country_id = $country;
                })->find($this->requestData->itemID);
                if ($item) {
                    $checkItem = $this->cart->cartDetails()->where('item_id', $this->requestData->itemID)->where("item_type", $itemType)->first();
                    if ($checkItem) {
                        $cartItems = $checkItem;
                    } else {
                        $cartItems = new \OlaHub\UserPortal\Models\CartItems;
                    }
                    if (isset($this->requestData->customImage) || isset($this->requestData->customText)) {
                        $custom = [
                            'image' => isset($this->requestData->customImage) ? $this->requestData->customImage : '',
                            'text' => isset($this->requestData->customText) ? $this->requestData->customText : ''
                        ];
                        $cartItems->customize_data = serialize($custom);
                    }
                    $cartItems->item_id = $item->id;
                    $cartItems->shopping_cart_id = $this->cart->id;
                    $cartItems->merchant_id = $item->merchant_id;
                    $cartItems->store_id = $item->store_id;
                    $cartItems->item_type = $itemType;
                    $cartItems->created_by = app('session')->get('tempID');
                    $cartItems->updated_by = app('session')->get('tempID');
                    $cartItems->unit_price = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item, TRUE);
                    $cartItems->quantity = isset($this->requestData->itemQuantity) && $this->requestData->itemQuantity > 0 ? $this->requestData->itemQuantity : 1;
                    $cartItems->total_price = (float) $cartItems->unit_price * $cartItems->quantity;
                    if ($this->celebration) {
                        $likers['user_id'][] = app('session')->get('tempID');
                        $cartItems->paricipant_likers = serialize($likers);
                    }
                    if ($cartItems->save()) {
                        $totalPrice = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
                        $this->cart->country_id = $country;
                        $this->cart->save();
                        if ($this->celebration) {
                            $this->handleAddItemToCelebration($totalPrice);
                        }
                        return TRUE;
                    }
                }
                break;
            case "designer":
                $itemMain = \OlaHub\UserPortal\Models\DesginerItems::whereIn("item_ids", [$this->requestData->itemID])->orWhere("_id", $this->requestData->itemID)->first();
                if ($itemMain) {
                    $item = false;
                    if (isset($itemMain->items) && count($itemMain->items) > 0) {
                        foreach ($itemMain->items as $oneItem) {
                            if ($oneItem["item_id"] == $this->requestData->itemID) {
                                $item = (object) $oneItem;
                            }
                        }
                    }
                    if (!$item) {
                        $item = $itemMain;
                    }
                    $checkItem = $this->cart->cartDetails()->where('item_id', $item->item_id)->where("item_type", $itemType)->first();
                    if ($checkItem) {
                        $cartItems = $checkItem;
                    } else {
                        $cartItems = new \OlaHub\UserPortal\Models\CartItems;
                    }
                    if (isset($this->requestData->customImage) || isset($this->requestData->customText)) {
                        $custom = [
                            'image' => isset($this->requestData->customImage) ? $this->requestData->customImage : '',
                            'text' => isset($this->requestData->customText) ? $this->requestData->customText : ''
                        ];
                        $cartItems->customize_data = serialize($custom);
                    }
                    $cartItems->item_id = $item->item_id;
                    $cartItems->shopping_cart_id = $this->cart->id;
                    $cartItems->merchant_id = $itemMain->designer_id;
                    $cartItems->store_id = $itemMain->designer_id;
                    $cartItems->item_type = $itemType;
                    $cartItems->created_by = app('session')->get('tempID');
                    $cartItems->updated_by = app('session')->get('tempID');
                    $cartItems->unit_price = $item->item_price;
                    $cartItems->quantity = isset($this->requestData->itemQuantity) && $this->requestData->itemQuantity > 0 ? $this->requestData->itemQuantity : 1;
                    $cartItems->total_price = (float) $cartItems->unit_price * $cartItems->quantity;
                    if ($this->celebration) {
                        $likers['user_id'][] = app('session')->get('tempID');
                        $cartItems->paricipant_likers = serialize($likers);
                    }
                    if ($cartItems->save()) {
                        $totalPrice = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
                        //                        $this->cart->total_price = $totalPrice;
                        //                        $this->cart->save();
                        if ($this->celebration) {
                            $this->handleAddItemToCelebration($totalPrice);
                        }
                        return TRUE;
                    }
                }
                break;
        }

        return FALSE;
    }

    private function handleRemoveItemFromCelebration($totalPrice)
    {
        if ($this->celebration && $totalPrice >= 0) {
            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)->get();
            $price = $totalPrice / $participants->count();
            $this->celebration->participant_count = $participants->count();
            $this->celebration->gifts_count = \OlaHub\UserPortal\Models\CartItems::where("shopping_cart_id", $this->cart->id)->count();
            $this->celebration->save();
            foreach ($participants as $participant) {
                $participant->amount_to_pay = $price;
                $participant->save();
            }
        }
    }

    private function handleAddItemToCelebration($totalPrice)
    {
        if ($this->celebration && $totalPrice >= 0) {
            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)->get();
            $price = $totalPrice / $participants->count();
            $this->celebration->participant_count = $participants->count();
            $this->celebration->gifts_count = \OlaHub\UserPortal\Models\CartItems::where("shopping_cart_id", $this->cart->id)->count();
            $this->celebration->save();
            foreach ($participants as $participant) {
                $participant->amount_to_pay = $price;
                $participant->save();
                if ($participant->user_id != app('session')->get('tempID')) {
                    $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                    $notification->type = 'celebration';
                    $notification->content = "notifi_addGiftCelebration";
                    $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                    $notification->celebration_title = $this->celebration->title;
                    $notification->celebration_id = $this->celebration->id;
                    $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                    $notification->read = 0;
                    $notification->for_user = $participant->user_id;
                    $notification->save();
                }
            }
        }
    }

    private function handleStoreItemsResponse($itemData)
    {
        $item = $itemData;
        $itemName = isset($item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') : NULL;
        $itemDescription = isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description') : NULL;
        $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item);
        $itemImage = $this->setItemImageData($item);
        $itemOwner = $this->setItemOwnerData($item);
        $itemAttrs = $this->setItemSelectedAttrData($item);
        //$productAttributes = $this->setAttrData($item);
        $itemFinal = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item, true, false);
        $country = \OlaHub\UserPortal\Models\Country::find($item->country_id);
        $currency = $country->currencyData;
        $return = [
            "productID" => isset($item->id) ? $item->id : 0,
            "productType" => 'store',
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
            "productName" => $itemName,
            "productDescription" => str_limit(strip_tags($itemDescription), 350, '.....'),
            "productInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($item),
            "productPrice" => $itemPrice['productPrice'],
            "productDiscountedPrice" => $itemPrice["productHasDiscount"] ? $itemPrice['productDiscountedPrice'] : $itemPrice["productPrice"],
            "productHasDiscount" => $itemPrice['productHasDiscount'],
            "productQuantity" => 1,
            "productCurrency" => isset($currency) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency->code) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency("JOD"),
            "productTotalPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice((float) $itemFinal, false),
            "productImage" => $itemImage,
            "productOwner" => $itemOwner['productOwner'],
            "productOwnerName" => $itemOwner['productOwnerName'],
            "productOwnerSlug" => $itemOwner['productOwnerSlug'],
            "productselectedAttributes" => $itemAttrs,
            //"productAttributes" => $productAttributes,
        ];
        return $return;
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

    public function saveCookie()
    {
        setcookie("userCheck", $this->NotLoginCartItems["cookieId"], 2592000, "/", "localhost", false, false);
        if (isset($this->NotLoginCartItems["cookieId"]) && $this->NotLoginCartItems["cookieId"]) {
        }
        $_COOKIE["cookie"] = $this->NotLoginCartItems["cookieId"];
        //var_dump($_COOKIE["cookie"]);
        app("session")->put("userCheck", "wwww");
        return response(["status" => true], 200)->withCookie(new Cookie("userCheck", $this->NotLoginCartItems["cookieId"], 2592000, "/", "localhost"));
    }

    private function getDesignerItemData($itemData, $designerItemId)
    {

        $return["productID"] = isset($itemData->item_id) ? $itemData->item_id : 0;
        $return["productType"] = 'designer';
        $return["productQuantity"] = 1;
        $return["productSlug"] = isset($itemData->item_slug) ? $itemData->item_slug : null;
        $return["productName"] = isset($itemData->item_title) ? $itemData->item_title : null;
        $return["productDescription"] = isset($itemData->item_description) ? $itemData->item_description : null;
        $return["productInStock"] = isset($itemData->item_stock) ? $itemData->item_stock : 0;
        $return["productOwner"] = isset($itemData->designer_id) ? $itemData->designer_id : 0;
        $return["productOwnerName"] = isset($itemData->designer_name) ? $itemData->designer_name : null;
        $return["productOwnerSlug"] = isset($itemData->designer_slug) ? $itemData->designer_slug : null;
        $return["productImage"] = $this->setDesignerItemImageData($itemData);

        $itemPrice = $this->setDesignerPriceData($itemData);
        $return["productPrice"] = $itemPrice["productPrice"];
        $return["productDiscountedPrice"] = $itemPrice["productDiscountedPrice"];
        $return["productHasDiscount"] = $itemPrice["productHasDiscount"];

        $item = false;

        if ($itemData->item_id != $designerItemId) {

            foreach ($itemData->items as $one) {
                $oneItem = (object) $one;
                if (isset($oneItem->item_id) && $oneItem->item_id == $designerItemId) {
                    $item = $oneItem;
                }
            }
        }

        if ($item) {
            $return["productID"] = isset($item->item_id) ? $item->item_id : 0;
            $return["productSlug"] = isset($item->item_slug) ? $item->item_slug : null;
            $return["productImage"] = $this->setDesignerItemImageData($item);

            $itemPrice = $this->setDesignerPriceData($item);
            $return["productPrice"] = $itemPrice["productPrice"];
            $return["productDiscountedPrice"] = $itemPrice["productDiscountedPrice"];
            $return["productHasDiscount"] = $itemPrice["productHasDiscount"];
        }

        return $return;
    }

    private function setDesignerItemImageData($item)
    {
        $images = [];
        if (isset($item->item_images)) {
            $images = $item->item_images;
        } elseif (isset($item->item_image)) {
            $images = $item->item_image;
        }
        if (count($images) > 0 && $images[0]) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDesignerPriceData($item)
    {
        $return["productPrice"] = isset($item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_price) : 0;
        $return["productDiscountedPrice"] = isset($item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_price) : 0;
        $return["productHasDiscount"] = false;
        if (isset($item->item_original_price) && $item->item_original_price && strtotime($item->discount_start_date) <= time() && strtotime($item->discount_end_date) >= time()) {
            $return["productDiscountedPrice"] = isset($item->item_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_price) : 0;
            $return["productPrice"] = isset($item->item_original_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->item_original_price) : 0;
            $return["productHasDiscount"] = true;
        }

        return $return;
    }
}
