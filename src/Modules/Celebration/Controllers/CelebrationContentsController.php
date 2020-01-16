<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\CelebrationContentsModel;

class CelebrationContentsController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $uploadVideoData;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->uploadVideoData = $request->all();
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function addParticipantWishText() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Add Participant Wish Text"]);
       
        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationContentsModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('id', $this->requestData['celebrationUser'])->where('celebration_id', $this->requestData['celebrationId'])->first();
        if (!$participant) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'UserNotParticipant', 'code' => 400]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'UserNotParticipant', 'code' => 400], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Add celebration wish text for participant"]);
        
        $participant->personal_message = $this->requestData['celebrationWishText'];
        $participant->save();
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'WishMessageSuccussfully', 'code' => 200]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            
        return response(['status' => true, 'msg' => 'WishMessageSuccussfully', 'code' => 200], 200);
    }

    public function uploadCelebrationVideo() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Upload celebration video"]);
       
        $this->requestData = isset($this->uploadVideoData) ? $this->uploadVideoData : [];

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(CelebrationContentsModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('id', $this->requestData['celebrationUser'])->where('celebration_id', $this->requestData['celebrationId'])->first();
        if (!$participant) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'UserNotParticipant', 'code' => 401]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            
            return response(['status' => false, 'msg' => 'UserNotParticipant', 'code' => 401], 200);
        }
        $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['celebrationVideo'], DEFAULT_IMAGES_PATH . "celebrations/" . $this->requestData['celebrationId'], "celebrations/" . $this->requestData['celebrationId'], false);
        
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Checking if array key exists for upload celebration video", "action_startData" => $uploadResult]);
        if (array_key_exists('path', $uploadResult)) {
            $celebrationContent = CelebrationContentsModel::where('celebration_id', $this->requestData['celebrationId'])->where('created_by', $this->requestData['celebrationUser'])->first();
            if (!$celebrationContent) {
                $celebrationContent = new CelebrationContentsModel;
                $celebrationContent->created_by = $this->requestData['celebrationUser'];
                $celebrationContent->celebration_id = $this->requestData['celebrationId'];
            }
            $celebrationContent->reference = $uploadResult['path'];
            $celebrationContent->save();
             (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'WishVideoSuccussfully', 'code' => 200]]);
             (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            
            return response(['status' => true, 'msg' => 'WishVideoSuccussfully', 'code' => 200], 200);
        } else {
           (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $uploadResult]);
           (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            
            response($uploadResult, 200);
        }
    }

    public function uploadMediaTopublishedCelebration() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Upload media to published celebration"]);
       
        $this->requestData = isset($this->uploadVideoData) ? $this->uploadVideoData : [];
        if (isset($this->requestData) && count($this->requestData) > 0) {

            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->where('celebration_id', $this->requestData['celebrationId'])->first();
            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $this->requestData['celebrationId'])->where('celebration_status', 4)->first();

            if (!$participant && $celebration->user_id != app('session')->get('tempID')) {
               (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'uploadImagesAndViedoToCelebration', 'code' => 400]]);
               (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            
                return response(['status' => false, 'msg' => 'uploadImagesAndViedoToCelebration', 'code' => 400], 200);
            }


            $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['celebrationMedia'], DEFAULT_IMAGES_PATH . "celebrations/" . $this->requestData['celebrationId'], "celebrations/" . $this->requestData['celebrationId'], false);
            
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Checking if array key exists for upload media to published celebration", "action_startData" => $uploadResult]);
            if (array_key_exists('path', $uploadResult)) {
                $celebrationContent = new CelebrationContentsModel;
                $celebrationContent->created_by = $participant->id;
                $celebrationContent->celebration_id = $this->requestData['celebrationId'];
                $celebrationContent->reference = $uploadResult['path'];
                $celebrationContent->save();
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'uploadMediaSuccussfully', 'code' => 200]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
               
                return response(['status' => true, 'msg' => 'uploadMediaSuccussfully', 'code' => 200], 200);
            } else {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $uploadResult]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                response($uploadResult, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                
        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

    public function scheduleCelebration() {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Schedule celebration"]);
       
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Checking if celebration existance to schedule"]);
        if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId']) {
            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $this->requestData['celebrationId'])->where('created_by', app('session')->get('tempID'))->first();
            if ($celebration && $celebration->created_by == app('session')->get('tempID')) {
                $celebration->celebration_status = 4;
                $celebration->original_celebration_date = "";
                $celebration->save();
                $participantsData = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where("celebration_id", $celebration->id)->get();
                $participantsId = [];
                foreach ($participantsData as $participant) {
                    $participantsId[] = $participant->user_id;
                }
                $celebrationParticipants = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $participantsId)->get();
                $celebrationOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $celebration->user_id)->first();
                foreach ($celebrationParticipants as $oneUser) {
                    if ($oneUser->mobile_no && $oneUser->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($oneUser->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    } else if ($oneUser->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendScheduleCelebration($oneUser, $celebration->title, $celebration->id, $celebrationOwner->first_name . ' ' . $celebrationOwner->last_name);
                    }
                }

                $creatorBill = \OlaHub\UserPortal\Models\UserBill::where("pay_for", $celebration->id)->first();
                $creatorBillDetails = $creatorBill->billDetails;
                $grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($creatorBillDetails);
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantScheduledOrderCelebration($grouppedMers, $creatorBill, $celebration, $celebrationOwner);
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesScheduledOrderCelebration($grouppedMers, $creatorBill, $celebration, $celebrationOwner);
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function changeDateBeforeSchedule() {
         (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Celebration", 'function_name' => "Change date before schedule"]);
       
         (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start changing date for schedule"]);
        if (isset($this->requestData['celebrationDate']) && $this->requestData['celebrationDate'] && isset($this->requestData['celebrationId']) && $this->requestData['celebrationId']) {
            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $this->requestData['celebrationId'])->first();

            if ($this->requestData['celebrationDate'] >= date("Y-m-d H:i:s", strtotime("+2 days")) && $this->requestData['celebrationDate'] <= $celebration->original_celebration_date) {

                $celebration->celebration_date = $this->requestData['celebrationDate'];
                $celebration->save();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($celebration, '\OlaHub\UserPortal\ResponseHandlers\CelebrationResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
                
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End changing date for schedule"]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response($return, 200);
            }
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'ThisDateIsInvalid', 'code' => 500]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'ThisDateIsInvalid', 'code' => 500], 200);
        }
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

}
