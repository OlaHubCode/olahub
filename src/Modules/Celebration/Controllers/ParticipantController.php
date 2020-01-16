<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\CelebrationModel;
use OlaHub\UserPortal\Models\CelebrationParticipantsModel;

class ParticipantController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    private $celebration;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function createNewParticipant() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "createNewParticipant"]);
       

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationParticipantsModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->where('created_by', app('session')->get('tempID'))->first();

        if ($this->celebration && $this->requestData['userId'] != $this->celebration->user_id) {
            $existParticipant = CelebrationParticipantsModel::where('user_id', $this->requestData['userId'])->where('celebration_id', $this->requestData['celebrationId'])->first();
            if ($existParticipant) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'ThisUserAlreadyParticipant', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'ThisUserAlreadyParticipant', 'code' => 500], 200);
            }
            $creatorData = $this->celebration->creatorUser;
            $celebrationTitle = $this->celebration->title;
            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
            $notification->type = 'celebration';
            $notification->content = "notifi_addParticipantCelebration";
            $notification->user_name = $creatorData->first_name . " " . $creatorData->last_name;
            $notification->celebration_title = $celebrationTitle;
            $notification->celebration_id = $this->celebration->id;
            $notification->avatar_url = $creatorData->profile_picture;
            $notification->read = 0;
            $notification->for_user = $this->requestData['userId'];
            $notification->save();

            return $this->participant();
        }

        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function deleteParticipant() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "deleteParticipant"]);
       

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationParticipantsModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->where('created_by', app('session')->get('tempID'))->first();
        if ($this->celebration) {
            $participant = CelebrationParticipantsModel::where('user_id', $this->requestData['userId'])->where('celebration_id', $this->requestData['celebrationId'])->first();
            if ($participant && $this->celebration->created_by != $this->requestData['userId'] && $this->celebration->user_id != $this->requestData['userId']) {
                $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $participant->user_id)->first();
                if ($userMongo) {
                    $userMongo->pull('celebrations', (int) $participant->celebration_id, true);
                }
                $participant->delete();
                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $celebrationTitle = $this->celebration->title;
                $notification->type = 'celebration';
                $notification->content = "notifi_removeParticipantCelebration";
                $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                $notification->celebration_title = $celebrationTitle;
                $notification->celebration_id = $this->requestData['celebrationId'];
                $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                $notification->read = 0;
                $notification->for_user = $this->requestData['userId'];
                $notification->save();
                $this->celebration->participant_count = $this->celebration->participant_count - 1;
                $this->celebration->save();
                (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
               $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'ParticipantDeleted', 'code' => 200]]);
               $log->saveLogSessionData();
                return response(['status' => true, 'msg' => 'ParticipantDeleted', 'code' => 200], 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAllowDeleteParticipant', 'code' => 400]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NotAllowDeleteParticipant', 'code' => 400], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function approveParticipantRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "approveParticipantRequest"]);
       

        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
            $participant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])
                    ->where('user_id', app('session')->get('tempID'))
                    ->where('is_approved', 0)
                    ->first();
            if ($participant) {
                $participant->is_approved = 1;
                $participant->save();
                $celebration = CelebrationModel::where('id', $participant->celebration_id)->first();
                $creator = \OlaHub\UserPortal\Models\UserModel::where('id', $celebration->created_by)->first();

                if ($creator->mobile_no && $creator->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAcceptCelebration($creator, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAcceptCelebration($creator, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title);
                } else if ($creator->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAcceptCelebration($creator, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title);
                } else if ($creator->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAcceptCelebration($creator, app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name, $celebration->title);
                }

                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'celebration';
                $notification->content = "notifi_acceptParticipantCelebration";
                $notification->user_name = app('session')->get('tempData')->first_name . ' ' . app('session')->get('tempData')->last_name;
                $notification->celebration_title = $celebration->title;
                $notification->celebration_id = $this->requestData['celebrationId'];
                $notification->avatar_url = app('session')->get('tempData')->profile_picture;
                $notification->read = 0;
                $notification->for_user = $celebration->created_by;
                $notification->save();

                $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', (int) app('session')->get('tempID'))->first();
                if ($userMongo) {
                    $userMongo->push('celebrations', (int) $participant->celebration_id, true);
                }


                $removeNotification = \OlaHub\UserPortal\Models\NotificationMongo::where('type', 'celebration')->where('celebration_id', $this->requestData['celebrationId'])->where('for_user', app('session')->get('tempID'))->first();
                if ($removeNotification) {
                    $removeNotification->delete();
                }


                $log->setLogSessionData(['response' => ['status' => true, 'data' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler'), 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => true, 'data' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler'), 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function rejectParticipantRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "rejectParticipantRequest"]);
       

        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
            $participant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])
                    ->where('user_id', app('session')->get('tempID'))
                    ->where('is_approved', 0)
                    ->where('is_creator', '!=', 1)
                    ->first();
            if ($participant) {
                $participant->delete();
                $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
                $this->celebration->participant_count = $this->celebration->participant_count - 1;
                $this->celebration->save();
                (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
                $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'You Reject Celebration Participant Request', 'code' => 200]]);
                $log->saveLogSessionData();

                $removeNotification = \OlaHub\UserPortal\Models\NotificationMongo::where('type', 'celebration')->where('celebration_id', $this->requestData['celebrationId'])->where('for_user', app('session')->get('tempID'))->first();
                if ($removeNotification) {
                    $removeNotification->delete();
                }



                return response(['status' => true, 'msg' => 'RejectCelebration', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' =>['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function leaveCelebration() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "leaveCelebration"]);
       
        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
            $participant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])
                    ->where('user_id', app('session')->get('tempID'))
                    ->where('is_approved', 1)
                    ->where('is_creator', '!=', 1)
                    ->first();
            if ($participant) {
                $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $participant->user_id)->first();
                if ($userMongo) {
                    $userMongo->pull('celebrations', (int) $participant->celebration_id, true);
                }
                $participant->delete();
                $this->celebration = CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
                $this->celebration->participant_count = $this->celebration->participant_count - 1;
                $this->celebration->save();

                $userData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'celebration';
                $notification->content = "notifi_leaveCelebration";
                $notification->user_name = $userData->first_name . " " . $userData->last_name;
                $notification->celebration_title = $this->celebration->title;
                $notification->celebration_id = $this->requestData['celebrationId'];
                $notification->avatar_url = $userData->profile_picture;
                $notification->read = 0;
                $notification->for_user = $this->celebration->created_by;
                $notification->save();

                (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
                $log->setLogSessionData(['response' =>['status' => true, 'msg' => 'You Leave Celebration', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => true, 'msg' => 'LeaveCelebration', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function participant() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "participant"]);
       
        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->requestData['userId'])->first();
        if ($user) {
            $participant = new CelebrationParticipantsModel;
            $participant->celebration_id = $this->requestData['celebrationId'];
            $participant->user_id = $this->requestData['userId'];
            $participant->save();
            $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->celebration->user_id)->first();
            if (!$user->invited_by) {
                if ($user->mobile_no && $user->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $this->celebration->title);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $this->celebration->title);
                } else if ($user->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $this->celebration->title);
                } else if ($user->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $this->celebration->title);
                }
            } else {
                $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                $user->password = $password;
                $user->save();
                if ($user->mobile_no && $user->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $password);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $password);
                } else if ($user->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $password);
                } else if ($user->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserCelebrationInvition($user, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name, $this->celebration->id, $password);
                }
            }

            $this->celebration->participant_count = $this->celebration->participant_count + 1;
            $this->celebration->save();
            (new \OlaHub\UserPortal\Helpers\CelebrationHelper)->saveCelebrationCart($this->celebration);
            $celebrationParticipant = CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)->where('user_id', $this->requestData['userId'])->first();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebrationParticipant, '\OlaHub\UserPortal\ResponseHandlers\CelebrationParticipantResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }

        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function ListCelebrationParticipants() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Celebration", 'function_name' => "ListCelebrationParticipants"]);
       
        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
            $participants = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('user_id', '!=', app('session')->get('tempID'))->get();
            $loginedParticipant = CelebrationParticipantsModel::where('celebration_id', $this->requestData['celebrationId'])->where('user_id', app('session')->get('tempID'))->first();
            $returnOne = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($loginedParticipant, '\OlaHub\UserPortal\ResponseHandlers\CelebrationParticipantResponseHandler');
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($participants, '\OlaHub\UserPortal\ResponseHandlers\CelebrationParticipantResponseHandler');
            array_unshift($return['data'], $returnOne['data']);
            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
       $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
       $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

}
