<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\CelebrationModel;
use OlaHub\UserPortal\Models\CelebrationParticipantsModel;

class GiftController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $celebration;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function commitCelebration()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Commit celebration"]);

        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId']) {
            if (isset($this->requestData['giftId']) && count($this->requestData['giftId']) <= 0) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'selectAtLeastOneGift', 'code' => 406, 'errorData' => []]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'selectAtLeastOneGift', 'code' => 406, 'errorData' => []], 200);
            }
            $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
            if ($this->celebration->celebration_date == date("Y-m-d", strtotime("-3 days")) || $this->celebration->celebration_date == date("Y-m-d", strtotime("-2 days")) || $this->celebration->celebration_date == date("Y-m-d", strtotime("-1 days")) || $this->celebration->celebration_date == date("Y-m-d")) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'dateOvered', 'code' => 406, 'errorData' => []]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'dateOvered', 'code' => 406, 'errorData' => []], 200);
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start commit celebration"]);
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
            $this->updateCommitCart();
            $this->updateCommitParticipant();
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
            $this->celebration->commit_date = date("Y-m-d H:i:s");
            $this->celebration->celebration_status = 2;
            $this->celebration->save();

            $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('user_id', '!=', app('session')->get('tempID'))->get();
            if (!empty($participants)) {
                foreach ($participants as $participant) {
                    $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'celebration';
                    $notification->content = "notifi_commitCelebration";
                    // $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                    // $notification->celebration_title =  $this->celebration->title;
                    $notification->celebration_id = $this->requestData['celebrationId'];
                    // $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                    $notification->read = 0;
                    $notification->user_id = $participantData->id;
                    $notification->save();
                }
            }

            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }


    private function updateCommitParticipant()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Update commit participant"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Update commit participant in celebration"]);
        $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->get();
        foreach ($participants as $participant) {
            if ($participant->is_approved != 1) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete participant who didn't accept celebration after committed"]);
                $participant->delete();
                $this->celebration->participant_count = $this->celebration->participant_count - 1;
                $this->celebration->save();
            } else {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Add participant to pay for committed celebration"]);
                $participant->payment_status = 2;
                $participant->save();
                $userData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                if ($userData->mobile_no && $userData->email && $participant->is_creator != 1) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendCommitedCelebration($userData, $this->celebration->id, $this->celebration->title);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendCommitedCelebration($userData, $this->celebration->id, $this->celebration->title);
                } else if ($userData->mobile_no && $participant->is_creator != 1) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendCommitedCelebration($userData, $this->celebration->id, $this->celebration->title);
                } else if ($userData->email && $participant->is_creator != 1) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendCommitedCelebration($userData, $this->celebration->id, $this->celebration->title);
                }
            }
        }
    }

    private function updateCommitCart()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Update commit cart"]);

        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $this->requestData['celebrationId'])->first();
        $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->whereIn('id', $this->requestData['giftId'])->where('shopping_cart_id', $cart->id)->get();
        if (count($cartItems->toArray()) > 0) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Remove unselected gifts"]);
            foreach ($cartItems as $cartItem) {
                if ($cartItem->item_type == 'designer') {
                    $item = \OlaHub\UserPortal\Models\DesginerItems::where("id", $cartItem->item_id)->first();
                    if ($item) {
                        if ((int) $item->item_stock <= 0) {
                            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') . 'isOutOfStock', 'code' => 500]]);
                            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                            return response(['status' => false, 'msg' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') . 'isOutOfStock', 'code' => 500], 200);
                        }
                    } else {
                        return response(['status' => false, 'msg' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') . 'isOutOfStock', 'code' => 500], 200);
                    }
                } else {
                    $item = \OlaHub\UserPortal\Models\CatalogItem::withoutGlobalScope('country')->where('id', $cartItem->item_id)->first();
                }
                $cartItem->is_approved = 1;
                $cartItem->save();
            }
        } else {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'selectAtLeastOneGift', 'code' => 406, 'errorData' => []]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => false, 'msg' => 'selectAtLeastOneGift', 'code' => 406, 'errorData' => []], 200);
        }
        \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->whereNotIn('id', $this->requestData['giftId'])->where('shopping_cart_id', $cart->id)->delete();
    }

    public function unCommitCelebration()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Uncommit celebration"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Uncommit celebration"]);
        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId']) {
            $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
            $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->get();
            foreach ($participants as $participant) {
                $participant->payment_status = 1;
                $participant->save();
            }
            $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $this->requestData['celebrationId'])->first();
            $cartItems = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
            if (count($cartItems->toArray()) > 0) {
                foreach ($cartItems as $cartItem) {
                    $cartItem->is_approved = 0;
                    $cartItem->save();
                }
            }
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
            $this->celebration->commit_date = NULL;
            $this->celebration->celebration_status = 1;
            $this->celebration->save();
            $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('user_id', '!=', app('session')->get('tempID'))->get();
            if (!empty($participants)) {
                foreach ($participants as $participant) {
                    $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'celebration';
                    $notification->content = "notifi_uncommitCelebration";
                    // $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                    // $notification->celebration_title =  $this->celebration->title;
                    $notification->celebration_id = $this->requestData['celebrationId'];
                    // $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                    $notification->read = 0;
                    $notification->user_id = $participantData->id;
                    $notification->save();
                }
            }
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }


    public function likeCelebrationGift()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Like celebration gift"]);

        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0 && isset($this->requestData['celebrationGiftId']) && $this->requestData['celebrationGiftId'] > 0) {
            $cartItem = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('id', $this->requestData['celebrationGiftId'])->first();
            $participant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('is_approved', 1)->first();
            if ($cartItem && $participant) {
                $likers = unserialize($cartItem->paricipant_likers);
                if (is_array($likers['user_id']) && in_array(app('session')->get('tempID'), $likers['user_id'])) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyLike', 'code' => 500]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'alreadyLike', 'code' => 500], 200);
                }
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Like celebration gifts"]);
                array_push($likers['user_id'], app('session')->get('tempID'));
                $cartItem->paricipant_likers = serialize($likers);
                $saved = $cartItem->save();
                if ($saved) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouLikeGift', 'code' => 200]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                    return response(['status' => true, 'msg' => 'YouLikeGift', 'code' => 200], 200);
                }
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
