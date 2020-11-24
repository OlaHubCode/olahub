<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\RegistryModel;
use OlaHub\UserPortal\Models\RegistryUsersModel;
use phpDocumentor\Reflection\Types\True_;

class RegistryParticipantController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    private $registry;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function createParticipants()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' create_Participants');

        $validator = RegistryUsersModel::validateMultiUserData($this->requestData);

        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }


        $this->registry = RegistryModel::where('id', $this->requestData['registryId'])->first();
        $existParticipants = RegistryUsersModel::whereIn('user_id', $this->requestData['usersId'])->where('registry_id', $this->requestData['registryId'])->pluck('user_id')->toArray();
        $usersId = $this->requestData['usersId'];
        $numArray = array_map('intval', $usersId);

        if ($existParticipants) {
            $usersId = array_diff($numArray, $existParticipants);
        }
        if ($usersId) {
            $log = new \OlaHub\UserPortal\Helpers\LogHelper();
            foreach ($usersId as $userId) {
                $this->participant($userId);
                if ($this->registry && $userId != app('session')->get('tempID') && $this->registry->is_published) {
                    $notification = new \OlaHub\UserPortal\Models\Notifications();
                    $notification->type = 'registry';
                    $notification->content = "notifi_addParticipantRegistry";
                    $notification->registry_id = $this->registry->id;
                    $notification->user_id = $userId;
                    $notification->friend_id = app('session')->get('tempID');
                    $notification->save();

                    $userData = app('session')->get('tempData');

                    $targe = \OlaHub\UserPortal\Models\UserModel::where('id', $userId)->first();
                    \OlaHub\UserPortal\Models\Notifications::sendFCM(
                        $targe->id,
                        "registry_part_add",
                        array(
                            "type" => "registry_part_add",
                            "registryId" => $this->registry->id,
                            "registryTitle" => $this->registry->title,
                            "username" => "$userData->first_name $userData->last_name",
                        ),
                        $targe->lang,
                        "$userData->first_name $userData->last_name",
                        $this->registry->title
                    );
                }
            }
            $participants = RegistryUsersModel::where('registry_id', $this->requestData['registryId'])->paginate(30);
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($participants, '\OlaHub\UserPortal\ResponseHandlers\RegistryParticipantResponseHandler');

            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }


    public function deleteParticipant()
    {

        $log = new \OlaHub\UserPortal\Helpers\Logs();

        $userData = app('session')->get('tempData');
        $log->saveLog($userData->id, $this->requestData, ' deleteParticipant');

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(RegistryUsersModel::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log = new \OlaHub\UserPortal\Helpers\LogHelper();

            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $this->registry = RegistryModel::where('id', $this->requestData['registryId'])->first();
        if ($this->registry && (app('session')->get('tempID') == $this->requestData['userId'] ||
            app('session')->get('tempID') == $this->registry->user_id)) {
            $participant = RegistryUsersModel::where('user_id', $this->requestData['userId'])->where('registry_id', $this->requestData['registryId'])->first();
            if ($participant && $this->registry->user_id != $this->requestData['userId']) {
                $participant->delete();
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


    private function participant($user_id)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Registry", 'function_name' => "participant"]);

        $user = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $user_id)->first();
        if ($user) {
            $participant = new RegistryUsersModel;
            $participant->registry_id = $this->requestData['registryId'];
            $participant->user_id = $user_id;
            $participant->save();
            if ($user_id != app('session')->get('tempID') && $this->registry->is_published) {
                if ($user->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendRegisterUserRegistryInvition($user, $this->registry->user_name, $this->registry->id, $this->registry->title);
                }
                if ($user->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendRegisterUserRegistryInvition($user, $this->registry->user_name, $this->registry->id, $this->registry->title);
                }
            }

            return true;
        }

        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function ListRegistryParticipants()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Registry", 'function_name' => "ListRegistryParticipants"]);

        if (isset($this->requestData['registryId']) && $this->requestData['registryId'] > 0) {
            $participants = RegistryUsersModel::where('registry_id', $this->requestData['registryId'])->paginate(30);
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($participants, '\OlaHub\UserPortal\ResponseHandlers\RegistryParticipantResponseHandler');
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

    public function inviteNotRegisterUsers()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Registry", 'function_name' => "participant"]);

        $validator = RegistryUsersModel::validateNotRegisterUserData($this->requestData);

        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }

        foreach ($this->requestData['users'] as $user) {
            $is_phone = is_numeric($user);
            $this->registry = RegistryModel::where('id', $this->requestData['registryId'])->first();
            $registryOwner = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $this->registry->user_id)->first();
            if ($is_phone) {
                $send = (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserRegistryInvition($user, $this->registry->user_name, $this->registry->id, $this->registry->title);
            } else {
                $send = (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserRegistryInvition($user, $this->registry->user_name, $this->registry->id, $this->registry->title);
            }
        }
        $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'UsersInvitedSuccessfully', 'code' => 200]]);
        $log->saveLogSessionData();
        return response(['status' => true, 'msg' => 'UsersInvitedSuccessfully', 'code' => 200], 200);
    }
}
