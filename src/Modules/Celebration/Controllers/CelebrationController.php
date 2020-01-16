<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\CelebrationModel;
use OlaHub\UserPortal\Models\CelebrationParticipantsModel;
use OlaHub\UserPortal\Models\CelebrationShippingAddressModel;

class CelebrationController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    private $celebration;
    private $cartData;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function createNewCelebration() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Create new celebration"]);

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start creating new celebration"]);
        $saved = $this->saveCelebrationData();
        if ($saved) {
            //$this->firstParticipant();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End creating new celebration"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response($return, 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'InternalServerError', 'code' => 500]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
    }

    public function createCelebrationByCalendar() {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Create celebration by calendar"]);

        if (isset($this->requestData['calendarId']) && $this->requestData['calendarId'] > 0 && isset($this->requestData['celebrationDate']) && CelebrationModel::validateDate($this->requestData) && isset($this->requestData['celebrationTitle']) && $this->requestData['celebrationTitle']) {
            $calendarData = \OlaHub\UserPortal\Models\CalendarModel::where('id', $this->requestData['calendarId'])->first();
            if ($calendarData) {
                if ($calendarData->user_id == app('session')->get('tempID')) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'celebrationToYourself', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'celebrationToYourself', 'code' => 400], 200);
                }
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start creating celebration according to dates in calender"]);
                $this->celebration = new CelebrationModel;
                $this->celebration->user_id = $calendarData->user_id;
                $this->celebration->created_by = app('session')->get('tempID');
                $this->celebration->celebration_date = $this->requestData['celebrationDate'];
                $this->celebration->original_celebration_date = $this->requestData['celebrationDate'];
                $this->celebration->occassion_id = $calendarData->occasion_id;
                $this->celebration->title = $this->requestData['celebrationTitle'];
                $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $calendarData->user_id)->first();
                $this->celebration->country_id = $owner->country_id;
                $this->celebration->participant_count = $this->celebration->participant_count + 1;
                $saved = $this->celebration->save();
                if ($saved) {
                    $this->firstParticipant();
                    CelebrationShippingAddressModel::saveShippingAddress($this->celebration->id, $this->celebration->country_id, $this->requestData);
                    (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
                    $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
                    $return['status'] = TRUE;
                    $return['code'] = 200;
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End creating celebration according dates in calender"]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                    return response($return, 200);
                }
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function createCelebrationByCart() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Create celebration by cart"]);

        if (isset($this->requestData['cartId']) && $this->requestData['cartId'] <= 0) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start creating celebration according to items in cart"]);
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        $this->cartData = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('id', $this->requestData['cartId'])->first();
        if ($this->cartData) {
            if ($this->cartData->country_id != app('session')->get('def_country')->id) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'notMakeCelebration', 'code' => 400]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                return response(['status' => false, 'msg' => 'notMakeCelebration', 'code' => 400], 200);
            }


            $saved = $this->saveCelebrationData();
            if ($saved) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End creating celebration according to items in cart"]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

    public function updateCelebration() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Update celebration"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start updating celebration"]);
        if (CelebrationModel::validateCelebrationId($this->requestData) && CelebrationModel::validateDate($this->requestData, $this->requestData['celebrationId'])) {
            $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
            foreach ($this->requestData as $input => $value) {
                if (isset(CelebrationModel::$columnsMaping[$input])) {
                    $this->celebration->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CelebrationModel::$columnsMaping, $input)} = $value;
                }
            }

            $this->celebration->created_by = app('session')->get('tempID');
            $saved = $this->celebration->save();
            if ($saved) {
                CelebrationShippingAddressModel::saveShippingAddress($this->celebration->id, $this->celebration->country_id, $this->requestData);
                if (isset($this->requestData['celebrationCountry']) && $this->requestData['celebrationCountry'] > 0) {
                    $celebrationCart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $this->celebration->id)->first();
                    $celebrationCart->country_id = $this->requestData['celebrationCountry'];
                    $celebrationCart->save();
                }
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($this->celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End updating celebration"]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function deleteCelebration() {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Delete celebration"]);

        if (CelebrationModel::validateCelebrationId($this->requestData)) {
            $celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
            $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->get();
            if ($celebration) {
                if ($celebration->created_by != app('session')->get('tempID')) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAuthorizedToDeleteCelebration', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'NotAuthorizedToDeleteCelebration', 'code' => 400], 200);
                } elseif ($celebration->commit_date != null) {
                    foreach ($participants as $participant) {
                        if ($participant->payment_status == 3)
                            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'participantsPaied', 'code' => 400]]);
                        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                        return response(['status' => false, 'msg' => 'participantsPaied', 'code' => 400], 200);
                    }
                }
            }
            $this->deleteCelebrationDetails($celebration);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete celebration"]);
            $celebration->delete();
            foreach ($participants as $Participant) {
                if ($Participant->user_id != $celebration->created_by) {

                    $participantData = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $Participant->user_id)->first();
                    $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $celebration->user_id)->first();

                    /* $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                      $notification->type = 'celebration';
                      $notification->content = app('session')->get('tempData')->first_name .' '. app('session')->get('tempData')->last_name . " delete $celebration->title celebration";
                      $notification->celebration_id = $this->requestData['celebrationId'];
                      $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                      $notification->read = 0;
                      $notification->for_user = $Participant->user_id;
                      $notification->save(); */


                    if ($participantData->mobile_no && $participantData->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendDeletedCelebration($participantData, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendDeletedCelebration($participantData, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($participantData->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendDeletedCelebration($participantData, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($participantData->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendDeletedCelebration($participantData, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    }
                }
            }(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Remove notifications related to celebration"]);
            $removeNotifications = \OlaHub\UserPortal\Models\NotificationMongo::where('type', 'celebration')->where('celebration_id', $this->requestData['celebrationId'])->get();
            foreach ($removeNotifications as $removeNotification) {
                if ($removeNotification) {
                    $removeNotification->delete();
                }
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'celebrationDeletedSuccessfully', 'code' => 200]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            return response(['status' => true, 'msg' => 'celebrationDeletedSuccessfully', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'celebrationDeletedSuccessfully', 'code' => 200]]);
        $log->saveLogSessionData();

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);

        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function deleteCelebrationDetails($celebration) {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Delete celebration details"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete celebration details", "action_startData" => $celebration]);
        $cart = \OlaHub\UserPortal\Models\Cart::withoutGlobalScope('countryUser')->where('celebration_id', $celebration->id)->first();
        $celebrationContents = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('celebration_id', $celebration->id)->get();

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete gift items related to celebration"]);
        if ($cart) {
            $cartDetails = \OlaHub\UserPortal\Models\CartItems::withoutGlobalScope('countryUser')->where('shopping_cart_id', $cart->id)->get();
            if (count($cartDetails) > 0) {
                foreach ($cartDetails as $cartDetail) {
                    $cartDetail->delete();
                }
            }
            $cart->delete();
        }(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete videos and images related to celebration"]);
        if (count($celebrationContents) > 0) {
            foreach ($celebrationContents as $celebrationContent) {
                $celebrationContent->delete();
            }
        }(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete shipping address related to celebration"]);
        if ($celebration->shippingAddress) {
            $celebration->shippingAddress()->delete();
        }(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Delete celebration participants related to celebration"]);
        if ($celebration->celebrationParticipants) {
            $celebration->celebrationParticipants()->delete();
        }
    }

    private function firstParticipant() {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "First participant"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Make celebration creator participant in this celebration"]);

        $participant = new CelebrationParticipantsModel;
        $participant->celebration_id = $this->celebration->id;
        $participant->user_id = $this->celebration->created_by;
        $participant->is_approved = 1;
        $participant->is_creator = 1;
        $participant->save();
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', (int) $participant->user_id)->first();
        if ($userMongo) {
            $userMongo->push('celebrations', (int) $this->celebration->id, true);
        }
    }

    private function saveCelebrationData() {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Save celebration data"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Save celebration Data"]);

        if (app('session')->get('tempID') == $this->requestData['celebrationOwner']) {
            return false;
        }
        $this->celebration = new CelebrationModel;
        foreach ($this->requestData as $input => $value) {
            if (isset(CelebrationModel::$columnsMaping[$input])) {
                $this->celebration->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(CelebrationModel::$columnsMaping, $input)} = $value;
            }
        }

        $this->celebration->created_by = app('session')->get('tempID');
        $this->celebration->participant_count = $this->celebration->participant_count + 1;
        $this->celebration->original_celebration_date = $this->requestData['celebrationDate'];
        $saved = $this->celebration->save();
        if ($saved) {
            if ($this->cartData) {
                $this->cartData->celebration_id = $this->celebration->id;
                $this->cartData->user_id = null;
                $this->cartData->save();
            }
            $this->firstParticipant();
            CelebrationShippingAddressModel::saveShippingAddress($this->celebration->id, $this->celebration->country_id, $this->requestData);
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
            return true;
        }
        return false;
    }

    public function ListCelebrations() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "List celebrations"]);

        $participants = CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->get();

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "List user celebrations"]);
        if (count($participants) > 0) {
            foreach ($participants as $participant) {
                $celebrationId[] = $participant->celebration_id;
            }

            $celebration = CelebrationModel::whereIn('id', $celebrationId)->orderBy('created_at', 'desc')->whereHas("cart", function($q) {
                        $q->withoutGlobalScope("countryUser");
                    })->paginate(10);
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
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

    public function getOneCelebration() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Get one celebration"]);


        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
            $celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
            $participant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('user_id', app('session')->get('tempID'))->first();
            if (!$participant) {
                if ($celebration->celebration_status < 5 && $celebration->user_id == app('session')->get('tempID')) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'authorizedToOpenCelebration', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'authorizedToOpenCelebration', 'code' => 400], 200);
                } elseif ($celebration->user_id != app('session')->get('tempID')) {
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'authorizedToOpenCelebration', 'code' => 400]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'authorizedToOpenCelebration', 'code' => 400], 200);
                }
            }(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Check celebration existance to show its details for user"]);
            if ($celebration) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Show details of selected celebration"]);
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
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
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getUserShippingAddress() {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Get user shipping address"]);

        if (isset($this->requestData['userId']) && $this->requestData['userId']) {
            $countryId = 0;
            if (isset($this->requestData['countryId']) && $this->requestData['countryId']) {
                $countryId = $this->requestData['countryId'];
            } else {
                $countryId = app('session')->get('def_country')->id;
            }

            $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->requestData['userId'])->first();
            $shippingAddress = \OlaHub\UserPortal\Models\UserShippingAddressModel::withoutGlobalScope('currentUser')->where('user_id', $this->requestData['userId'])->where('country_id', $countryId)->first();

            if ($shippingAddress) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Get user shipping address details"]);
                $country = \OlaHub\UserPortal\Models\Country::where('id', $shippingAddress->country_id)->first();
                $return["data"] = [
                    "shipping_address_city" => isset($shippingAddress->shipping_address_city) ? $shippingAddress->shipping_address_city : NULL,
                    "shipping_address_state" => isset($shippingAddress->shipping_address_state) ? $shippingAddress->shipping_address_state : NULL,
                    "shipping_address_email" => isset($shippingAddress->shipping_address_email) ? $shippingAddress->shipping_address_email : NULL,
                    "shipping_address_phone_no" => isset($shippingAddress->shipping_address_phone_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber($shippingAddress->shipping_address_phone_no) : NULL,
                    "shipping_address_address_line1" => isset($shippingAddress->shipping_address_address_line1) ? $shippingAddress->shipping_address_address_line1 : NULL,
                    "shipping_address_address_line2" => isset($shippingAddress->shipping_address_address_line2) ? $shippingAddress->shipping_address_address_line2 : NULL,
                    "shipping_address_zip_code" => isset($shippingAddress->shipping_address_zip_code) ? $shippingAddress->shipping_address_zip_code : NULL,
                    "celebrationCountry" => isset($shippingAddress->country_id) ? (string) $shippingAddress->country_id : NULL,
                    "celebrationCountryCode" => isset($country) ? strtolower($country->two_letter_iso_code) : NULL,
                    "userEmail" => isset($user->email) ? $user->email : NULL,
                    "userPhoneNumber" => isset($user->mobile_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber($user->mobile_no) : NULL,
                ];
            } else {
                $country = \OlaHub\UserPortal\Models\Country::where('id', $countryId)->first();
                $return["data"] = [
                    "shipping_address_city" => NULL,
                    "shipping_address_state" => NULL,
                    "shipping_address_email" => NULL,
                    "shipping_address_phone_no" => NULL,
                    "shipping_address_address_line1" => NULL,
                    "shipping_address_address_line2" => NULL,
                    "shipping_address_zip_code" => NULL,
                    "celebrationCountry" => (string) $countryId,
                    "celebrationCountryCode" => isset($country) ? strtolower($country->two_letter_iso_code) : NULL,
                    "userEmail" => isset($user->email) ? $user->email : NULL,
                    "userPhoneNumber" => isset($user->mobile_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber($user->mobile_no) : NULL,
                ];
            }

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

    public function getCreatorShippingAddress() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Get creator shipping address"]);

        $cart = \OlaHub\UserPortal\Models\Cart::getUserCart(app('session')->get('tempID'));
        if (!$cart) {
            throw new NotAcceptableHttpException(404);
        }
        $shippingAddress = \OlaHub\UserPortal\Models\UserShippingAddressModel::checkUserShippingAddress(app('session')->get('tempID'), $cart->country_id);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Get celebration creator shipping address details"]);
        $return["data"] = [
            "shipping_address_city" => $shippingAddress['addressCity'],
            "shipping_address_state" => $shippingAddress['addressState'],
            "shipping_address_phone_no" => isset(app('session')->get('tempData')->mobile_no) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->handleUserPhoneNumber(app('session')->get('tempData')->mobile_no) : NULL,
            "shipping_address_address_line1" => $shippingAddress['addressAddress'],
            "shipping_address_zip_code" => $shippingAddress['addressZipCode'],
            "celebrationCountry" => isset($cart->country_id) ? (string) $cart->country_id : NULL,
        ];
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

}
