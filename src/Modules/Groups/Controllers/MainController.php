<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\groups;
use OlaHub\UserPortal\Models\GroupMembers;

class MainController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $uploadImage;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->uploadImage = $request->all();
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function listGroups()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroups"]);

        $groups = GroupMembers::getGroups(app('session')->get('tempID'))->paginate(12);
        if (!$groups) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollectionPginate($groups, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
        $return['status'] = TRUE;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function listAllGroups()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroups"]);

        $groups = GroupMembers::getGroups(app('session')->get('tempID'))->get();
        if (!$groups) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($groups, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
        $return['status'] = TRUE;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function createNewGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "createNewGroup"]);

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(groups::$columnsMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $group = new groups;
        foreach ($this->requestData as $input => $value) {
            if (isset(groups::$columnsMaping[$input])) {
                if ($input == 'groupInterests')
                    $group->interests = implode(",", $value);
                else
                    $group->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(groups::$columnsMaping, $input)} = $value;
            }
        }
        $group->creator = app('session')->get('tempID');
        $group->slug = \OlaHub\UserPortal\Helpers\CommonHelper::createSlugFromString($this->requestData['groupName']);
        $saved = $group->save();
        if ($saved) {
            $member = new GroupMembers;
            $member->group_id = $group->id;
            $member->user_id = app('session')->get('tempID');
            $member->save();
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
    }

    public function getOneGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getOneGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
            }
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function updateGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "updateGroup"]);

        if (isset($this->requestData) && $this->requestData && isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'allowUpdateGroup', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'allowUpdateGroup', 'code' => 400], 200);
            }
            $oldGroupPostApprove = $group->posts_approve;
            foreach ($this->requestData as $input => $value) {
                if (isset(groups::$columnsMaping[$input])) {
                    if ($input == 'groupInterests')
                        $group->interests = implode(",", $value);
                    else
                        $group->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(groups::$columnsMaping, $input)} = $value;
                }
            }
            $saved = $group->save();
            if (!$saved) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'InternalServerError', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
            }

            if ($oldGroupPostApprove == 1 && $this->requestData['groupPostApprove'] == 0) {
                if (isset($this->requestData['isChangeApprovePost']) && $this->requestData['isChangeApprovePost'])
                    \OlaHub\UserPortal\Models\Post::where('group_id', $group->id)->update(['is_approve' => 1]);
                else
                    \OlaHub\UserPortal\Models\Post::where('group_id', $group->id)->where('is_approve', 0)->delete();
            }

            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function deleteGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "deleteGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'deleteThisGroup', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'deleteThisGroup', 'code' => 400], 200);
            }
            $group->delete();
            GroupMembers::where('group_id', $group->id)->delete();
            \OlaHub\UserPortal\Models\Post::where('group_id', $group->id)->delete();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouDeleteGroupSuccessfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouDeleteGroupSuccessfully', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function inviteUserToGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "inviteUserToGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && count($this->requestData["userId"]) > 0) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }

            $inviterData = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            $usersData = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->whereIn('id', $this->requestData["userId"])->get();
            foreach ($usersData as $user) {
                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'group';
                $notification->content = "notifi_inviteCommuntity";
                $notification->user_id = $user->id;
                $notification->group_id = $group->id;
                $notification->friend_id = $inviterData->id;
                $notification->save();

                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $user->id,
                    "invite_group",
                    array(
                        "type" => "invite_group",
                        "groupId" => $group->id,
                        "user_data" => $inviterData,
                    ),
                    $user->lang,
                    $group->name,
                    "$inviterData->first_name $inviterData->last_name",
                    $group->name
                );

                $member = (new GroupMembers)->where('user_id', $user->id)->where('group_id', $group->id)->first();
                if (!$member) {
                    $member = new GroupMembers;
                    $member->group_id = $group->id;
                    $member->user_id = $user->id;
                    $member->status = 2;
                    $member->save();
                }
            }

            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouInviteSuccessfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouInviteSuccessfully', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function removeGroupMember()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "inviteUserToGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID') || $group->creator == $this->requestData["userId"]) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'removeMemberGroup', 'code' => 400]]);
                $log->saveLogSessionData();

                return response(['status' => false, 'msg' => 'removeMemberGroup', 'code' => 400], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', $this->requestData["userId"])->delete();
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function approveAdminGroupRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveAdminGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID') || $group->creator == $this->requestData["userId"]) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NotAllowApproveUser', 'code' => 400]]);
                $log->saveLogSessionData();

                return response(['status' => false, 'msg' => 'NotAllowApproveUser', 'code' => 400], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', $this->requestData["userId"])->update(['status' => 1]);

            $notification = new \OlaHub\UserPortal\Models\Notifications();
            $notification->type = 'group';
            $notification->content = "notifi_adminApproveReq";
            $notification->user_id = $this->requestData["userId"];
            $notification->friend_id = app('session')->get('tempID');
            $notification->group_id = $group->id;
            $notification->save();

            $userData = app('session')->get('tempData');
            $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $this->requestData["userId"])->first();
            \OlaHub\UserPortal\Models\Notifications::sendFCM(
                $this->requestData["userId"],
                "accept_member",
                array(
                    "type" => "accept_member",
                    "groupId" => $group->id,
                    "user_data" => $userData,
                ),
                $owner->lang,
                $group->name,
                "$userData->first_name $userData->last_name",
                $group->name
            );

            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function approveUserGroupRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveUserGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', app('session')->get('tempID'))->update(['status' => 1]);

            // $notification = new \OlaHub\UserPortal\Models\Notifications();
            // $notification->type = 'group';
            // $notification->content = "notifi_acceptCommunity";
            // $notification->group_id = $group->_id;
            // $notification->read = 0;
            // $notification->user_id = $group->creator;
            // $notification->save();

            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function rejectAdminGroupRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectAdminGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'notAllowRejectUserRequest', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'notAllowRejectUserRequest', 'code' => 400], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', $this->requestData["userId"])->delete();
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function cancelAdminInvite()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "cancelAdminInvite"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'notAllowRejectUserRequest', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'notAllowRejectUserRequest', 'code' => 400], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', $this->requestData["userId"])->delete();
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function rejectUserGroupRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectUserGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', app('session')->get('tempID'))->delete();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouRejectGroupRequest', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouRejectGroupRequest', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function leaveGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "leaveGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator == app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'leaveYouAreCreator', 'code' => 401]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'leaveYouAreCreator', 'code' => 401], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', app('session')->get('tempID'))->delete();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouLeaveGroup', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouLeaveGroup', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function listGroupMembers()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroupMembers"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $allMembers = GroupMembers::getMembers($group->id);
            $members = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $allMembers->members)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
            $return['members'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($members, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
            $return['responses'] = [];
            $return['requests'] = [];
            if (count($allMembers->responses)) {
                $responses = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->whereIn('id', $allMembers->responses)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
                $return['responses'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($responses, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
            }
            if (count($allMembers->requests)) {
                $requests = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $allMembers->requests)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
                $return['requests'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($requests, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
            }

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

    public function joinPublicGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "joinPublicGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->where('privacy', 3)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $member = (new GroupMembers)->where('user_id', app('session')->get('tempID'))->where('group_id', $group->id)->first();
            if ($member) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500], 200);
            } else {
                $member = new GroupMembers;
                $member->group_id = $group->id;
                $member->user_id = app('session')->get('tempID');
                $member->status = 1;
                $member->save();
            }
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
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

    public function joinClosedGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "joinClosedGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->where('privacy', 2)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $member = (new GroupMembers)->where('user_id', app('session')->get('tempID'))->where('group_id', $group->id)->first();
            if ($member) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500], 200);
            } else {
                $member = new GroupMembers;
                $member->group_id = $group->id;
                $member->user_id = app('session')->get('tempID');
                $member->status = 3;
                $member->save();
            }

            $notification = new \OlaHub\UserPortal\Models\Notifications();
            $notification->type = 'group';
            $notification->content = "notifi_requestCommunity";
            $notification->user_id = $group->creator;
            $notification->friend_id = app('session')->get('tempID');
            $notification->group_id = $group->id;
            $notification->save();
            
            $userData = app('session')->get('tempData');
            $owner = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
            \OlaHub\UserPortal\Models\Notifications::sendFCM(
                $group->creator,
                "ask_group",
                array(
                    "type" => "ask_group",
                    "groupId" => $group->id,
                    "user_data" => $userData,
                ),
                $owner->lang,
                $group->name,
                "$userData->first_name $userData->last_name"
            );

            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $return['msg'] = "joinClosedGroup";
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function cancelJoinClosedGroup()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "cancelJoinClosedGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('id', $this->requestData["groupId"])->orWhere('slug', $this->requestData["groupId"])->where('privacy', 2)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            GroupMembers::where('group_id', $group->id)->where('user_id', app('session')->get('tempID'))->delete();
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $return['msg'] = "cancelJoinClosedSuccess";
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function uploadGroupImageAndCover()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "uploadGroupImageAndCover"]);

        $this->requestData = isset($this->uploadImage) ? $this->uploadImage : [];
        if (isset($this->requestData['groupImage']) && $this->requestData['groupImage'] && isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('id', $this->requestData['groupId'])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if ($group->creator != app('session')->get('tempID')) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'changeGroupImageOrCover', 'code' => 400]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'changeGroupImageOrCover', 'code' => 400], 200);
            }

            $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['groupImage'], DEFAULT_IMAGES_PATH . "/groups/" . $group->id, "groups/" . $group->id, false);

            if (array_key_exists('path', $uploadResult)) {
                if ($this->requestData['groupImageType'] == 'cover') {
                    $group->cover = $uploadResult['path'];
                } else {
                    $group->image = $uploadResult['path'];
                }
                $group->save();
                $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
                $return['status'] = TRUE;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            } else {
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                $logHelper->setLog($this->requestData, $uploadResult, 'changeGroupImageOrCover', $this->userAgent);
                response($uploadResult, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getBrandsRelatedGroupInterests()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getBrandsRelatedGroupInterests"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('id', $this->requestData['groupId'])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $gInts = implode("|", explode(",", $group->interests));

            if ($group && $group->only_my_stores) {
                $creatorUser = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
                $merchants = \OlaHub\UserPortal\Models\ItemStore::whereHas('merchantRelation', function ($q) {
                    $q->country_id = app('session')->get('def_country')->id;
                })->where('merchant_id', $creatorUser->for_merchant)->get();
            } else {
                $merchants = \OlaHub\UserPortal\Models\ItemStore::whereHas('merchantRelation', function ($q) use ($gInts) {
                    $q->country_id = app('session')->get('def_country')->id;
                    $q->whereRaw("CONCAT(',', interests, ',') REGEXP ',(" . $gInts . "),'");
                })->get();
            }

            $return = [];
            foreach ($merchants as $merchant) {
                $items = \OlaHub\UserPortal\Models\CatalogItem::where('merchant_id', $merchant->merchant_id)->where("store_id", $merchant->id)->where("is_parent", 1)->paginate(5);
                $itemData = [];
                foreach ($items as $item) {
                    $itemName = isset($item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') : NULL;
                    $price = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($item);
                    $images = $item->images;
                    $itemData[] = [
                        "itemId" => $item->id,
                        "itemName" => $itemName,
                        "itemPrice" => $price['productPrice'],
                        "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
                        "itemImage" => count($images) > 0 ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                    ];
                }
                if (count($itemData) > 0) {
                    $return["data"][] = [
                        "merchantId" => $merchant->id,
                        "merchantName" => $merchant->name,
                        "merchantSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($merchant, 'store_slug', $merchant->name),
                        "merchantLogo" => isset($merchant->image_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($merchant->image_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                        "itemData" => $itemData
                    ];
                }
            }

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

    public function getDesignersRelatedGroupInterests()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getDesignersRelatedGroupInterests"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('id', $this->requestData['groupId'])->orWhere('slug', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $gInts = implode("|", explode(",", $group->interests));

            if ($group && $group->only_my_stores) {
                $creatorUser = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
                $designers = \OlaHub\UserPortal\Models\DesignerItems::whereHas('designer')->where('designer_id', $creatorUser->for_merchant)->get();
            } else {
                $designers = \OlaHub\UserPortal\Models\Designer::whereRaw("CONCAT(',', interests, ',') REGEXP ',(" . $gInts . "),'")->get();
            }

            $return = [];
            foreach ($designers as $designer) {
                $designerData = \OlaHub\UserPortal\Models\Designer::find($designer->id);
                $items = \OlaHub\UserPortal\Models\DesignerItems::where("designer_id", $designer->id)->where("parent_item_id", 0)->paginate(5);
                $itemData = [];
                foreach ($items as $item) {
                    $price = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($item);
                    $images = $item->images;
                    $itemData[] = [
                        "itemId" => $item->id,
                        "itemName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name'),
                        "itemPrice" => $price['productPrice'],
                        "itemSlug" => $item->item_slug,
                        "itemImage" => count($images) > 0 ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                    ];
                }
                if (count($itemData) > 0) {
                    $return["data"][] = [
                        "designerId" => $designerData->id,
                        "designerName" => $designerData->brand_name,
                        "designerSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($designerData, 'designer_slug', $designerData->brand_name),
                        "designerLogo" => isset($designerData->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designerData->logo_ref) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                        "itemData" => $itemData
                    ];
                }
            }

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

    public function approveAdminPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveAdminPost"]);

        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = \OlaHub\UserPortal\Models\Post::where('post_id', $this->requestData['postId'])->where('is_approve', 0)->first();
            if ($post) {
                $group = groups::where('id', $post->group_id)->first();
                if ($group && $group->creator != app('session')->get('tempID')) {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'notAllowapprovePost', 'code' => 400]]);
                    $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'notAllowapprovePost', 'code' => 400], 200);
                }
                $post->is_approve = 1;
                $post->save();
                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'group';
                $notification->content = "notifi_ApprovepostGroup";
                $notification->user_id = $post->user_id;
                $notification->friend_id = $group->creator;
                $notification->group_id = $group->id;
                $notification->post_id = $post->post_id;
                $notification->save();
                $return['status'] = TRUE;
                $return['code'] = 200;
                $return['msg'] = "approvepostsuccessfully";
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function rejectGroupPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectGroupPost"]);

        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = \OlaHub\UserPortal\Models\Post::where('post_id', $this->requestData['postId'])->where('is_approve', 0)->first();
            if ($post) {
                $group = groups::where('id', $post->group_id)->first();
                if ($group && $group->creator != app('session')->get('tempID')) {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'notAllowrejectPost', 'code' => 400]]);
                    $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'notAllowrejectPost', 'code' => 400], 200);
                }
                $post->delete();
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'rejectpostsuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'rejectpostsuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function listPendingGroupPost()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listPendingGroupPost"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('id', $this->requestData['groupId'])->orWhere('slug', $this->requestData["groupId"])->where('posts_approve', 1)->first();
            if ($group) {
                $posts = \OlaHub\UserPortal\Models\Post::where('group_id', $group->id)->where('is_approve', 0)->get();
                if ($posts->count() > 0) {
                    $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($posts, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                    $return['status'] = TRUE;
                    $return['code'] = 200;
                    $log->setLogSessionData(['response' => $return]);
                    $log->saveLogSessionData();
                    return response($return, 200);
                }
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
