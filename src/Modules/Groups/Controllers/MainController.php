<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\groups;

class MainController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $uploadImage;
    protected $userAgent;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->uploadImage = $request->all();
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */
    public function listGroups() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroups"]);

        $groups = groups::whereIn('members', [app('session')->get('tempID')])->paginate(10);
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

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */
    public function listAllGroups() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroups"]);

        $groups = groups::whereIn('members', [app('session')->get('tempID')])->get();
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

    public function createNewGroup() {
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
                $group->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(groups::$columnsMaping, $input)} = $value;
            }
        }
        $group->members = [app('session')->get('tempID')];
        $group->creator = app('session')->get('tempID');
        $saved = $group->save();
        if ($saved) {
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->push('my_groups', $group->_id, true);
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->push('groups', $group->_id, true);
            \OlaHub\UserPortal\Models\Interests::whereIn('interest_id', $group->interests)->push('groups', $group->_id, true);
            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($group, '\OlaHub\UserPortal\ResponseHandlers\MainGroupResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
            return response($return, 200);
        }
    }

    public function getOneGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getOneGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
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

    public function updateGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "updateGroup"]);

        if (isset($this->requestData) && $this->requestData && isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
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
                if (isset($this->requestData['isChangeApprovePost']) && $this->requestData['isChangeApprovePost']) {
                    $notApprovedPosts = \OlaHub\UserPortal\Models\Post::where('group_id', $this->requestData["groupId"])->where('isApprove', 0)->get();
                    if ($notApprovedPosts->count() > 0) {
                        foreach ($notApprovedPosts as $post) {
                            $post->isApprove = 1;
                            $post->save();
                            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                            $notification->type = 'group';
                            $notification->content = "notifi_ApprovepostGroup";
                            $notification->user_name = "";
                            $notification->community_title = $group->name;
                            $notification->group_id = $post->group_id;
                            $notification->avatar_url = $group->avatar_url;
                            $notification->read = 0;
                            $notification->for_user = $post->user_id;
                            $notification->save();
                        }
                    }
                } else {
                    \OlaHub\UserPortal\Models\Post::where('group_id', $this->requestData["groupId"])->where('isApprove', 0)->delete();
                }
            }

            if (isset($this->requestData['groupPrivacy']) && $this->requestData['groupPrivacy']) {
                \OlaHub\UserPortal\Models\Post::where('group_id', $this->requestData["groupId"])->update(['privacy' => $this->requestData['groupPrivacy']]);
            }
            if (isset($this->requestData['groupName']) && $this->requestData['groupName']) {
                \OlaHub\UserPortal\Models\Post::where('group_id', $this->requestData["groupId"])->update(['group_title' => $this->requestData['groupName']]);
            }


            \OlaHub\UserPortal\Models\Interests::project(['interest_id' => ['$slice' => $group->interests]])->pull('groups', $group->_id);
            foreach ($group->interests as $gInterests) {
                $interestAdd = \OlaHub\UserPortal\Models\Interests::where('interest_id', $gInterests)->first();
                $interestAdd->push('groups', $this->requestData["groupId"], true);
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

    public function deleteGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "deleteGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
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
            \OlaHub\UserPortal\Models\Interests::project(['interest_id' => ['$slice' => $group->interests]])->pull('groups', $group->_id);
            \OlaHub\UserPortal\Models\UserMongo::project(['my_groups' => ['$slice' => $group->_id]])->pull('my_groups', $group->_id);
            \OlaHub\UserPortal\Models\UserMongo::project(['groups' => ['$slice' => $group->_id]])->pull('groups', $group->_id);
            \OlaHub\UserPortal\Models\UserMongo::project(['group_invitions' => ['$slice' => $group->_id]])->pull('group_invition', $group->_id);
            $group->delete();
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouDeleteGroupSuccessfully', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouDeleteGroupSuccessfully', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function inviteUserToGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "inviteUserToGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && count($this->requestData["userId"]) > 0) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
            if (!$group) {

                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }

            $group->push('responses', $this->requestData["userId"], true);
            \OlaHub\UserPortal\Models\UserMongo::whereIn('user_id', $this->requestData["userId"])->push('group_invitions', $group->_id, true);

            $inviterData = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            foreach ($this->requestData["userId"] as $user) {
                $userData = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope('notTemp')->where('id', $user)->first();
                if (!$userData->invited_by) {
                    $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                    $notification->type = 'group';
                    $notification->content = "notifi_inviteCommuntity";
                    $notification->user_name = $inviterData->first_name . " " . $inviterData->last_name;
                    $notification->community_title = $group->name;
                    $notification->group_id = $group->_id;
                    $notification->avatar_url = $inviterData->profile_picture;
                    $notification->read = 0;
                    $notification->for_user = $user;
                    $notification->save();
                } else {
                    $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                    $userData->password = $password;
                    $userData->save();
                    if ($userData->mobile_no && $userData->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserGroupInvition($userData, $inviterData->first_name . ' ' . $inviterData->last_name, $group, $password);
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserGroupInvition($userData, $inviterData->first_name . ' ' . $inviterData->last_name, $group, $password);
                    } else if ($userData->mobile_no) {
                        (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserGroupInvition($userData, $inviterData->first_name . ' ' . $inviterData->last_name, $group, $password);
                    } else if ($userData->email) {
                        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserGroupInvition($userData, $inviterData->first_name . ' ' . $inviterData->last_name, $group, $password);
                    }
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

    public function removeGroupMember() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "inviteUserToGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
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
            $group->pull('members', $this->requestData["userId"]);
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', $this->requestData["userId"])->pull('groups', $group->_id);
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

    public function approveAdminGroupRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveAdminGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->whereIn('requests', [$this->requestData["userId"]])->first();
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
            $group->push('members', $this->requestData["userId"], true);
            $group->pull('requests', $this->requestData["userId"], true);

            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
            $notification->type = 'group';
            $notification->content = "notifi_adminApproveReq";
            $notification->user_name = app('session')->get('tempData')->first_name . " " . app('session')->get('tempData')->last_name;
            $notification->community_title = $group->name;
            $notification->group_id = $group->_id;
            $notification->avatar_url = app('session')->get('tempData')->profile_picture;
            $notification->read = 0;
            $notification->for_user = $this->requestData["userId"];
            $notification->save();

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

    public function approveUserGroupRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveUserGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->project(['responses' => ['$slice' => app('session')->get('tempID')]])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $group->push('members', app('session')->get('tempID'), true);
            $group->pull('responses', app('session')->get('tempID'));
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->push('groups', $group->_id, true);
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->pull('group_invitions', $group->_id);


            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
            $notification->type = 'group';
            $notification->content = "notifi_acceptCommunity";
            $notification->user_name = app('session')->get('tempData')->first_name . " " . app('session')->get('tempData')->last_name;
            $notification->community_title = $group->name;
            $notification->group_id = $group->_id;
            $notification->avatar_url = app('session')->get('tempData')->profile_picture;
            $notification->read = 0;
            $notification->for_user = $group->creator;
            $notification->save();


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

    public function rejectAdminGroupRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectAdminGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->project(['requests' => ['$slice' => $this->requestData["userId"]]])->first();
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
            $group->pull('requests', $this->requestData["userId"]);
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

    public function cancelAdminInvite() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "cancelAdminInvite"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"] && isset($this->requestData["userId"]) && $this->requestData["userId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->project(['responses' => ['$slice' => $this->requestData["userId"]]])->first();
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
            $group->pull('responses', $this->requestData["userId"]);
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

    public function rejectUserGroupRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectUserGroupRequest"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->project(['responses' => ['$slice' => app('session')->get('tempID')]])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $group->pull('responses', app('session')->get('tempID'));
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->pull('group_invitions', $group->_id);
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouRejectGroupRequest', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouRejectGroupRequest', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function leaveGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "leaveGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->project(['members' => ['$slice' => app('session')->get('tempID')]])->first();
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
            $group->pull('members', app('session')->get('tempID'));
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->pull('groups', $group->_id);
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'YouLeaveGroup', 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'YouLeaveGroup', 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function listGroupMembers() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listGroupMembers"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $members = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $group->members)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
            $return['members'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($members, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
            $return['requests'] = [];
            $return['responses'] = [];
            if ($group->requests) {
                $requests = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $group->requests)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
                $return['requests'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($requests, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
            }
            if ($group->responses) {
                $responses = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->whereIn('id', $group->responses)->orderByRaw('CONCAT(first_name, " ", last_name) ASC')->get();
                $return['responses'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($responses, '\OlaHub\UserPortal\ResponseHandlers\MembersResponseHandler');
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

    public function joinPublicGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "joinPublicGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->where('privacy', 3)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if (in_array(app('session')->get('tempID'), $group->members)) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500], 200);
            }
            $group->push('members', app('session')->get('tempID'), true);
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->push('groups', $group->_id, true);
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

    public function joinClosedGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "joinClosedGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->where('privacy', 2)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if (in_array(app('session')->get('tempID'), $group->members)) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'alreadyMemberInGroup', 'code' => 500], 200);
            }
            $requests = $group->requests ? $group->requests : [];
            if (in_array(app('session')->get('tempID'), $requests)) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'alreadyRequestToGroup', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'alreadyRequestToGroup', 'code' => 500], 200);
            }
            $group->push('requests', app('session')->get('tempID'), true);

            $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
            $notification->type = 'group';
            $notification->content = "notifi_requestCommunity";
            $notification->user_name = app('session')->get('tempData')->first_name . " " . app('session')->get('tempData')->last_name;
            $notification->community_title = $group->name;
            $notification->group_id = $group->_id;
            $notification->avatar_url = app('session')->get('tempData')->profile_picture;
            $notification->read = 0;
            $notification->for_user = $group->creator;
            $notification->save();

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

    public function cancelJoinClosedGroup() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "cancelJoinClosedGroup"]);

        if (isset($this->requestData["groupId"]) && $this->requestData["groupId"]) {
            $group = groups::where('_id', $this->requestData["groupId"])->where('privacy', 2)->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            if (in_array(app('session')->get('tempID'), $group->members) || !in_array(app('session')->get('tempID'), $group->requests)) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'youDontSentRequest', 'code' => 500]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'youDontSentRequest', 'code' => 500], 200);
            }
            $group->pull('requests', app('session')->get('tempID'));
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

    public function uploadGroupImageAndCover() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "uploadGroupImageAndCover"]);

        $this->requestData = isset($this->uploadImage) ? $this->uploadImage : [];
        if (isset($this->requestData['groupImage']) && $this->requestData['groupImage'] && isset($this->requestData['groupId']) && $this->requestData['groupId']) {

            $group = groups::where('_id', $this->requestData['groupId'])->first();
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

            $uploadResult = \OlaHub\UserPortal\Helpers\GeneralHelper::uploader($this->requestData['groupImage'], DEFAULT_IMAGES_PATH . "/groups/" . $group->_id, "groups/" . $group->_id, false);

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
                $logHelper->setLog($this->requestData, $uploadResult, 'joinPublicGroup', $this->userAgent);
                response($uploadResult, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getBrandsRelatedGroupInterests() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getBrandsRelatedGroupInterests"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('_id', $this->requestData['groupId'])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $interests = \OlaHub\UserPortal\Models\Interests::whereIn('interest_id', $group->interests)->get();
            if (!$interests) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }

            if ($group && $group->onlyMyStores) {
                $creatorUser = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
                $merchants = \OlaHub\UserPortal\Models\ItemStore::whereHas('merchantRelation', function ($q) {
                            $q->country_id = app('session')->get('def_country')->id;
                        })->where('merchant_id', $creatorUser->for_merchant)->get();
                $itemIds = [];
                foreach ($interests as $interest) {
                    $itemIds = $interest->items;
                }
                if (count($itemIds) > 0) {
                    $items = \OlaHub\UserPortal\Models\CatalogItem::where('merchant_id', $creatorUser->for_merchant)->whereIn("id", $itemIds)->get();
                } else {
                    $items = \OlaHub\UserPortal\Models\CatalogItem::where('merchant_id', $creatorUser->for_merchant)->orderByRaw("RAND()")->get();
                }
            } else {
                $merchantIds = [];
                $itemIds = [];
                foreach ($interests as $interest) {
                    $merchantIds = $interest->merchants;
                    $itemIds = $interest->items;
                }
                $merchants = \OlaHub\UserPortal\Models\ItemStore::whereHas('merchantRelation', function ($q) {
                            $q->country_id = app('session')->get('def_country')->id;
                        })->whereIn('merchant_id', $merchantIds)->get();
            }



            $return = [];
            foreach ($merchants as $merchant) {
                $items = \OlaHub\UserPortal\Models\CatalogItem::whereIn('id', $itemIds)->where("store_id", $merchant->id)->paginate(5);
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
                    $return ["data"][] = [
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

    public function getDesignersRelatedGroupInterests() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "getDesignersRelatedGroupInterests"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('_id', $this->requestData['groupId'])->first();
            if (!$group) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }
            $interests = \OlaHub\UserPortal\Models\Interests::whereIn('interest_id', $group->interests)->get();

            if (!$interests) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'groupNotExist', 'code' => 204]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'groupNotExist', 'code' => 204], 200);
            }

            $interest_ids = [];
            foreach ($interests as $interest) {
                $interest_ids[] = strval($interest->interest_id);
            }
            if ($group && $group->onlyMyStores) {
                $creatorUser = \OlaHub\UserPortal\Models\UserModel::where('id', $group->creator)->first();
                $items = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_interest_id', $interest_ids)
                ->where('designer_id',$creatorUser->id)
                ->get();
                
            } else {
                $items = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_interest_id', $interest_ids)->get();
            }
            $designers_ids = [];
            $item_ids = [];
            foreach ($items as $item) {
                    if(! in_array($item->designer_id, $designers_ids) ){
                        $designers_ids [] = $item->designer_id;
                    }
                    $item_ids[] = $item->item_id;
                }


            $return = [];
            foreach ($designers_ids as $designer_id) {
                $designerData = \OlaHub\UserPortal\Models\Designer::find($designer_id);
                $items = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_id', $item_ids)->where("designer_id", $designer_id)->paginate(5);
                $itemData = [];
                foreach ($items as $item) {
                $itemName = isset($item->item_title) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'item_title') : NULL;
                $price = \OlaHub\UserPortal\Models\DesginerItems::checkPrice($item);
                $images = $item->item_images;
                $itemData[] = [
                    "itemId" => $item->item_id,
                    "itemName" => $itemName,
                    "itemPrice" => $price['productPrice'],
                    "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
                    "itemImage" => count($images) > 0 ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                ];
            }
                if (count($itemData) > 0) {
                    $return ["data"][] = [
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

    public function approveAdminPost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "approveAdminPost"]);

        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = \OlaHub\UserPortal\Models\Post::where('_id', $this->requestData['postId'])->where('isApprove', '!=', 1)->first();
            if ($post) {
                $group = groups::where('_id', $post->group_id)->first();
                if ($group && $group->creator != app('session')->get('tempID')) {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'notAllowapprovePost', 'code' => 400]]);
                    $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'notAllowapprovePost', 'code' => 400], 200);
                }
                $post->isApprove = 1;
                $post->save();
                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'group';
                $notification->content = "notifi_ApprovepostGroup";
                $notification->user_name = "";
                $notification->community_title = $group->name;
                $notification->group_id = $post->group_id;
                $notification->avatar_url = $group->avatar_url;
                $notification->read = 0;
                $notification->for_user = $post->user_id;
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

    public function rejectGroupPost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "rejectGroupPost"]);

        if (isset($this->requestData['postId']) && $this->requestData['postId']) {
            $post = \OlaHub\UserPortal\Models\Post::where('_id', $this->requestData['postId'])->where('isApprove', '!=', 1)->first();
            if ($post) {
                $group = groups::where('_id', $post->group_id)->first();
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

    public function listPendingGroupPost() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Groups", 'function_name' => "listPendingGroupPost"]);

        if (isset($this->requestData['groupId']) && $this->requestData['groupId']) {
            $group = groups::where('_id', $this->requestData['groupId'])->where('posts_approve', 1)->first();
            if ($group) {
                $posts = \OlaHub\UserPortal\Models\Post::where('group_id', $this->requestData['groupId'])->where('isApprove', '!=', 1)->get();
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
