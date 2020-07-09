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
    private $friends;
    private $calendar;
    protected $userAgent;
    private $cartCookie;
    protected $uploadVideoData;
    private $countryId;
    private $lang;
    private $request;
    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->request =  $return['requestData'];
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
        // $countryData = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->find($country);
        // if (!$countryData) {
        //     throw new NotAcceptableHttpException(404);
        // }
        $this->checkCart($type);
        $this->cart->shipped_to = $country == $this->cart->country_id ? NULL : $country;
        // $this->cart->shipment_fees = 44.90;
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
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
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
            //    var_dump($requestData);
            //     return;
        } else {
            throw new NotAcceptableHttpException(404);
        }
        // var_dump($this->request);
        // return;
        $log->saveLog($userData->id, $this->request, 'Add To Cart ');


        // $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        // $logHelper->setLog($this->requestData, $return, 'newCartItem', $this->userAgent);
        return response($return, 200);
    }

    public function removeCartItem($itemType = "store", $type = "default")
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $return = ['status' => FALSE, 'msg' => 'ProductNotCart', 'code' => 204];
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(Cart::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            $log->saveLog($userData->id, $this->request, 'Add To Cart ');

            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        if ($this->requestData->itemID == 'all') {
            $this->cart->cartDetails()->where('shopping_cart_id', $this->cart->id)->delete();
        } else {
            $data = false;
            $data = $this->cart->cartDetails()->where('item_id', $this->requestData->itemID)->where("item_type", $itemType)->first();
            if ($data) {
                if ($this->celebration) {
                    if ($this->celebration->commit_date || ($data->created_by != app('session')->get('tempID') && $this->celebration->created_by != app('session')->get('tempID'))) {
                        $log->saveLog($userData->id, $this->request, 'Add To Cart ');

                        return response(['status' => false, 'msg' => 'NotAllowToDeleteThisGift', 'code' => 400], 200);
                    }
                }
                $data->delete();
            }
        }
        $totalPrice = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
        $this->cart->total_price = $totalPrice;
        $this->cart->save();
        $this->handleRemoveItemFromCelebration($totalPrice);
        $return = [];
        $return['status'] = TRUE;
        $return['code'] = 200;
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
                    $itemData = \OlaHub\UserPortal\Models\DesignerItems::where('id', $designerItem)->first();
                    if ($itemData->item_stock > 0)
                        $return["data"][] = $this->getDesignerItemData($itemData);
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
            // $this->friends = $this->friends;
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
        if ($first && !$this->celebration) {
            $this->cart->shipped_to = null;
            $this->cart->for_friend = null;
            $this->cart->gift_date = null;
            $this->cart->save();
        }
        $countryData = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->countryId)->first();
        $countryTo = $this->celebration ? $this->celebration->country_id : ($this->cart->shipped_to ? $this->cart->shipped_to : $countryData->id);
        $defaultCountry = $this->celebration ? $this->celebration->country_id : $countryData->id;
        $shippingFees = \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($countryTo, $defaultCountry, $this->cart);
        $shippingFeesCelebration = \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($countryTo, $defaultCountry, $this->cart, $this->celebration);
        $this->cart->shipment_fees = $shippingFees['total'];
        $this->cart->shipment_details = serialize($shippingFeesCelebration['saving']);
        $this->cart->country_id = $countryData->id;
        $this->cart->save();

        if ($this->celebration) {
            if ($this->celebration->celebration_status > 2) {
                $celebrationID = $this->celebration->id;
                $cartDetails = \OlaHub\UserPortal\Models\UserBillDetails::whereHas("mainBill", function ($q) use ($celebrationID) {
                    $q->where("pay_for", $celebrationID);
                })->get();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($cartDetails, '\OlaHub\UserPortal\ResponseHandlers\CelebrationGiftDoneResponseHandler');
            } else {
                $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $this->cart->id)->orderBy('paricipant_likers', 'desc')->get();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($cartDetails, '\OlaHub\UserPortal\ResponseHandlers\CelebrationGiftResponseHandler');
            }
            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)
                ->where('user_id', app('session')->get('tempID'))->first();
            $pp = $this->celebration->celebrationParticipants->count();
            $total = $this->cart->total_price + $shippingFees['total'];
            $debt = number_format($total / $pp, 2);
            $debt = number_format($debt - fmod($debt, MOD_CELEBRATION), 2);
            if ($participant->is_creator)
                $total = ($total == ($debt * $pp) ? $debt : ($total - ($debt * $pp)) + $debt);
            else
                $total = $debt;
            $return['total'] = $total > 0 ? array(
                ['label' => 'subtotal', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->cart->total_price, true, $this->celebration->country_id), 'className' => "subtotal"],
                ['label' => 'shippingFees', 'value' => $shippingFees['shipping'], 'className' => "shippingFees"],
                ['label' => 'yourTotal', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total, true, $this->celebration->country_id), 'className' => "total"]
            ) : null;
        } else {
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
        $countryTo = $this->celebration ? $this->celebration->country_id : $countryData->id;
        if ($checkCart) {
            $this->cart = $checkCart;
            if ($this->cart->country_id == 0) {
                $this->cart->country_id = $countryTo;
                // $this->cart->country_id = 5;
                $this->cart->save();
            }
            (new \OlaHub\UserPortal\Helpers\CartHelper)->checkOutOfStockInCartItem($checkCart->id, $this->celebration);
        }

        if (!$this->cart) {
            if ($this->creatCart($type)) {
                $this->cart = $this->cartFilter($type);
                if ($this->cart->country_id == 0) {
                    $this->cart->country_id = $countryTo;
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
        $checkItem = $this->cart->cartDetails()->where('item_id', $this->requestData->itemID)->where("item_type", $itemType)->first();
        if ($checkItem) {
            $cartItems = $checkItem;
        } else {
            $cartItems = new \OlaHub\UserPortal\Models\CartItems;
        }
        switch ($itemType) {
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope("country")->whereHas('merchant', function ($q) use ($country) {
                    $q->withoutGlobalScope("country");
                    $q->country_id = $country;
                })->find($this->requestData->itemID);
                if ($item) {
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
                $item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $this->requestData->itemID)->first();
                if ($item) {
                    if (isset($this->requestData->customImage) || isset($this->requestData->customText)) {
                        $custom = [
                            'image' => isset($this->requestData->customImage) ? $this->requestData->customImage : '',
                            'text' => isset($this->requestData->customText) ? $this->requestData->customText : ''
                        ];
                        $cartItems->customize_data = serialize($custom);
                    }
                    $cartItems->item_id = $item->id;
                    $cartItems->shopping_cart_id = $this->cart->id;
                    $cartItems->merchant_id = $item->designer_id;
                    $cartItems->store_id = $item->designer_id;
                    $cartItems->item_type = $itemType;
                    $cartItems->created_by = app('session')->get('tempID');
                    $cartItems->updated_by = app('session')->get('tempID');
                    $cartItems->unit_price = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item, true);
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
            $userData = app('session')->get('tempData');
            foreach ($participants as $participant) {
                $participant->amount_to_pay = $price;
                $participant->save();
                if ($participant->user_id != $userData->id) {
                    $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $participantData->id,
                        "remove_gift",
                        array(
                            "type" => "remove_gift",
                            "celebrationId" => $this->celebration->id,
                            "celebrationTitle" => $this->celebration->title,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $participantData->lang,
                        "$userData->first_name $userData->last_name",
                        $this->celebration->title
                    );
                }
            }
        }
    }

    private function handleAddItemToCelebration($totalPrice)
    {
        if ($this->celebration && $totalPrice >= 0) {
            print_r($this->celebration);return;

            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)->get();
            $price = $totalPrice / $participants->count();
            $this->celebration->participant_count = $participants->count();
            $this->celebration->gifts_count = \OlaHub\UserPortal\Models\CartItems::where("shopping_cart_id", $this->cart->id)->count();
            $this->celebration->save();
            $userData = app('session')->get('tempData');
            foreach ($participants as $participant) {
                $participant->amount_to_pay = $price;
                $participant->save();
                if ($participant->user_id != app('session')->get('tempID')) {
                    $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'celebration';
                    $notification->content = "notifi_addGiftCelebration";
                    $notification->celebration_id = $this->celebration->id;
                    $notification->user_id = $participant->user_id;
                    $notification->friend_id = $participant->user_id;
                    $notification->save();

                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $participantData->id,
                        "add_gift",
                        array(
                            "type" => "add_gift",
                            "celebrationId" => $this->celebration->id,
                            "celebrationTitle" => $this->celebration->title,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $participantData->lang,
                        "$userData->first_name $userData->last_name",
                        $this->celebration->title
                    );
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
        $currency = isset($currency) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency("JOD");
        $return = [
            "productID" => isset($item->id) ? $item->id : 0,
            "productSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
            "productName" => $itemName,
            "productDescription" => str_limit(strip_tags($itemDescription), 350, '.....'),
            "productInStock" => \OlaHub\UserPortal\Models\CatalogItem::checkStock($item),
            "productPrice" => $itemPrice['productPrice'],
            "productDiscountedPrice" => $itemPrice["productHasDiscount"] ? $itemPrice['productDiscountedPrice'] : $itemPrice["productPrice"],
            "productHasDiscount" => $itemPrice['productHasDiscount'],
            "productQuantity" => 1,
            "productCurrency" => $currency,
            "productTotalPrice" => $itemFinal . " " . $currency,
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

    private function getDesignerItemData($itemData)
    {
        $designer = $itemData->designer;
        $return["productID"] = isset($itemData->id) ? $itemData->id : 0;
        $return["productType"] = 'designer';
        $return["productQuantity"] = 1;
        $return["productSlug"] = isset($itemData->item_slug) ? $itemData->item_slug : null;
        $return["productName"] = isset($itemData->name) ? $itemData->name : null;
        $return["productDescription"] = isset($itemData->description) ? str_limit(strip_tags($itemData->description), 350, '.....') : null;
        $return["productInStock"] = isset($itemData->item_stock) ? $itemData->item_stock : 0;
        $return["productOwner"] = isset($itemData->designer_id) ? $itemData->designer_id : 0;
        $return["productOwnerName"] = isset($designer->brand_name) ? $designer->brand_name : null;
        $return["productOwnerSlug"] = isset($designer->designer_slug) ? $designer->designer_slug : null;
        $return["productImage"] = $this->setDesignerItemImageData($itemData);

        $itemPrice = $this->setDesignerPriceData($itemData);
        $return["productPrice"] = $itemPrice["productPrice"];
        $return["productDiscountedPrice"] = $itemPrice["productDiscountedPrice"];
        $return["productHasDiscount"] = $itemPrice["productHasDiscount"];
        $return["productTotalPrice"] = $itemPrice["productPrice"];

        return $return;
    }

    private function setDesignerItemImageData($item)
    {
        $images = $item->images;
        if ($images) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
    }

    private function setDesignerPriceData($item)
    {
        $return["productPrice"] = isset($item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price) : 0;
        $return["productDiscountedPrice"] = isset($item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price) : 0;
        $return["productHasDiscount"] = false;
        if (isset($item->discounted_price) && $item->discounted_price && strtotime($item->discounted_price_end_date) <= time() && strtotime($item->discounted_price_end_date) >= time()) {
            $return["productDiscountedPrice"] = isset($item->price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->price) : 0;
            $return["productPrice"] = isset($item->discounted_price) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($item->discounted_price) : 0;
            $return["productHasDiscount"] = true;
        }

        return $return;
    }
}
