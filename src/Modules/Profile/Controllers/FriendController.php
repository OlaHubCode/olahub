<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class FriendController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */
    public function listFriendCalendar()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "listFriendCalendar"]);

        if (isset($this->requestData['userId']) && $this->requestData['userId'] > 0) {
            $userCalendar = \OlaHub\UserPortal\Models\CalendarModel::where('user_id', $this->requestData['userId'])->orderBy('calender_date', 'ASC')->get();
            if (count($userCalendar) > 0) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($userCalendar, '\OlaHub\UserPortal\ResponseHandlers\CalendarsResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function listFriendWishList()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "listFriendWishList"]);

        if (isset($this->requestData['userSlug']) && $this->requestData['userSlug']) {
            $user = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['userSlug'])->first();
            if ($user) {

                if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
                    $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $this->requestData['celebrationId'])->first();
                    if (!$celebration || $celebration->user_id != $user->id) {
                        $log->setLogSessionData(['response' => ['status' => false, 'authority' => 1, 'msg' => 'NoAllowToShowWishlist', 'code' => 400]]);
                        $log->saveLogSessionData();

                        return response(['status' => false, 'authority' => 1, 'msg' => 'NoAllowToShowWishlist', 'code' => 400], 200);
                    }
                    $userWishList = \OlaHub\UserPortal\Models\WishList::withoutGlobalScope('currentUser')->withoutGlobalScope('wishlistCountry')->whereIn('occasion_id', [$celebration->occassion_id, "0"])->where('user_id', $user->id)->where('type', "wish")->where('is_public', 1)->paginate(10);
                } else {
                    $userWishList = \OlaHub\UserPortal\Models\WishList::withoutGlobalScope('currentUser')->where('user_id', $user->id)->where('type', "wish")->where('is_public', 1)->paginate(10);
                }


                if ($userWishList->count() > 0) {
                    if (isset($this->requestData['celebrationId']) && $this->requestData['celebrationId'] > 0) {
                        //$return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($userWishList, '\OlaHub\UserPortal\ResponseHandlers\WishListsResponseHandler');

                        $return = (new \OlaHub\UserPortal\Helpers\WishListHelper)->getWishListData($userWishList);
                    } else {
                        $return["data"] = (new \OlaHub\UserPortal\Models\WishList)->setWishlistData($userWishList);
                    }
                    $return['status'] = true;
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
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getProfileInfo()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "getProfileInfo"]);

        if (isset($this->requestData['profile_url']) && $this->requestData['profile_url']) {
            $userProfile = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['profile_url'])
                ->where('id', "!=", app('session')->get('tempID'))
                ->where('is_active', '1')
                ->first();
            if ($userProfile) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userProfile, '\OlaHub\UserPortal\ResponseHandlers\FriendsResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;

                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function listUserUpComingEvent()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "listUserUpComingEvent"]);

        $user = \OlaHub\UserPortal\Models\UserModel::find(app('session')->get('tempID'));
        $friends = $user->friends;
        if (count($friends) > 0) {
            $friendsCalendar = \OlaHub\UserPortal\Models\CalendarModel::whereIn('user_id', $friends)->where('calender_date', "<=", date("Y-m-d H:i:s", strtotime("+30 days")))->where('calender_date', ">", date("Y-m-d H:i:s"))->orderBy('calender_date', 'desc')->get();
            if (count($friendsCalendar) > 0) {
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($friendsCalendar, '\OlaHub\UserPortal\ResponseHandlers\UpcomingEventsResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function sendFriendRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "sendFriendRequest"]);

        if (isset($this->requestData['profile_url'])) {
            $friend = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['profile_url'])->first();
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if ($user && $friend) {
                $friendID = $friend->id;
                $requests = (new \OlaHub\UserPortal\Models\Friends);
                $requests->user_id = $user->id;
                $requests->friend_id = $friendID;
                $requests->status = 2;
                $requests->save();

                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'user';
                $notification->content = "notifi_friendRequest";
                $notification->friend_id = $user->id;
                $notification->user_id = $friendID;
                $notification->save();
                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $friendID,
                    "friend_request",
                    array(
                        "type" => "friend_add",
                        "slug" => $user->profile_url,
                        "username" => "$user->first_name $user->last_name"
                    ),
                    $friend->lang
                );
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'sentSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'sentSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function cancelFriendRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "cancelFriendRequest"]);

        if (isset($this->requestData['profile_url'])) {
            $friend = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['profile_url'])->first();
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if ($user && $friend) {
                \OlaHub\UserPortal\Models\Friends::where('user_id', $user->id)->where('friend_id', $friend->id)->delete();
                \OlaHub\UserPortal\Models\Notifications::where('user_id', $friend->id)->where('friend_id', $user->id)
                    ->where('type', 'user')->delete();
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'canceledSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'canceledSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function rejectFriendRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "rejectFriendRequest"]);

        if (isset($this->requestData['profile_url'])) {
            $friend = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['profile_url'])->first();
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if ($user && $friend) {
                \OlaHub\UserPortal\Models\Friends::where('user_id', $friend->id)->where('friend_id', $user->id)->delete();
                \OlaHub\UserPortal\Models\Notifications::where('user_id', $user->id)->where('friend_id', $friend->id)
                    ->where('type', 'user')->delete();
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'rejectedSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'rejectedSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function acceptFriendRequest()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "acceptFriendRequest"]);

        if (isset($this->requestData['profile_url'])) {
            $friend = \OlaHub\UserPortal\Models\UserModel::where('profile_url', $this->requestData['profile_url'])->first();
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if ($user && $friend) {
                $accept = \OlaHub\UserPortal\Models\Friends::where('user_id', $friend->id)->where('friend_id', $user->id)->first();
                $accept->status = 1;
                $accept->save();

                \OlaHub\UserPortal\Models\Notifications::where('user_id', $user->id)->where('friend_id', $friend->id)->where('type', 'user')->delete();
                $notification = new \OlaHub\UserPortal\Models\Notifications();
                $notification->type = 'user';
                $notification->content = "notifi_acceptFriend";
                $notification->friend_id = $user->id;
                $notification->user_id = $friend->id;
                $notification->save();
                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $friend->id,
                    "accept_request",
                    array(
                        "type" => "friend_accept",
                        "slug" => $user->profile_url,
                        "username" => "$user->first_name $user->last_name"
                    ),
                    $friend->lang
                );
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'acceptedSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'acceptedSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'message' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        response(['status' => FALSE, 'message' => 'NoData', 'code' => 204], 200);
    }

    public function removeFriend()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "acceptFriendRequest"]);

        if (isset($this->requestData['user_id'])) {
            $friend = \OlaHub\UserPortal\Models\UserModel::where('id', $this->requestData['user_id'])->first();
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            if ($user && $friend) {
                \OlaHub\UserPortal\Models\Friends::whereRaw("user_id = " . app('session')->get('tempID') . " and  friend_id = " . $this->requestData['user_id'])
                    ->orWhereRaw("friend_id = " . app('session')->get('tempID') . " and  user_id = " . $this->requestData['user_id'])->delete();

                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'removeFriendSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'removeFriendSuccessfully', 'code' => 200], 200);
            }
        }

        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
