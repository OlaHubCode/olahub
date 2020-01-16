<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class FriendController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request) {
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
    public function listFriendCalendar() {
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

    public function listFriendWishList() {
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
                $log->setLogSessionData(['response' =>['status' => false, 'msg' => 'NoData', 'code' => 204]]);
                 $log->saveLogSessionData();
        
                return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getProfileInfo() {
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

    public function listUserUpComingEvent() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "listUserUpComingEvent"]);
        
        $user = \OlaHub\UserPortal\Models\UserMongo::find(app('session')->get('tempID'));
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

    public function sendFriendRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "sendFriendRequest"]);
        
        if (isset($this->requestData['profile_url'])) {
            $userid = $this->requestData['profile_url'];
            $friendMongo = \OlaHub\UserPortal\Models\UserMongo::where('profile_url', $userid)->first();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
            if ($userMongo && $friendMongo) {
                $friendID = $friendMongo->user_id;
                $friends = $userMongo->friends;
                $requests = $userMongo->requests;
                $response = $userMongo->responses;
                if (in_array($friendID, $friends) || in_array($friendID, $requests) || in_array($friendID, $response)) {
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500]]);
                $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500], 200);
                }
                $userMongo->push('requests', $friendID, true);
                $friendMongo->push('responses', app('session')->get('tempID'), true);

                $userData = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'user';
                $notification->content = "notifi_friendRequest";
                $notification->user_name = $userData->first_name . " " . $userData->last_name;
                $notification->profile_url = $userData->profile_url;
                $notification->avatar_url = $userData->profile_picture;
                $notification->read = 0;
                $notification->for_user = $friendMongo->user_id;
                $notification->save();
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'sentSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => TRUE, 'msg' => 'sentSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
                
        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function cancelFriendRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "cancelFriendRequest"]);
        
        if (isset($this->requestData['profile_url'])) {
            $userid = $this->requestData['profile_url'];
            $friendMongo = \OlaHub\UserPortal\Models\UserMongo::where('profile_url', $userid)->first();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
            if ($userMongo && $friendMongo) {
                $friendID = $friendMongo->user_id;
                $friends = $userMongo->friends;
                $requests = $userMongo->requests;
                $response = $userMongo->responses;
                if (in_array($friendID, $friends) || in_array($friendID, $response)) {
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500]]);
                $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500], 200);
                }
                $friendMongo->pull('responses', app('session')->get('tempID'));
                $userMongo->pull('requests', $friendID);

                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'canceledSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
                
                return response(['status' => TRUE, 'msg' => 'canceledSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
                
        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function rejectFriendRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "rejectFriendRequest"]);
        
        if (isset($this->requestData['profile_url'])) {
            $userid = $this->requestData['profile_url'];
            $friendMongo = \OlaHub\UserPortal\Models\UserMongo::where('profile_url', $userid)->first();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
            if ($userMongo && $friendMongo) {
                $friendID = $friendMongo->user_id;
                $friends = $userMongo->friends;
                $requests = $userMongo->requests;
                $response = $userMongo->responses;
                if (in_array($friendID, $friends) || in_array($friendID, $requests)) {
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500]]);
                    $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500], 200);
                }
                $friendMongo->pull('requests', app('session')->get('tempID'));
                $userMongo->pull('responses', $friendID);
                
                $userNotification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', (int) app('session')->get('tempID'))->where('type', 'user')->where('profile_url', $friendMongo->profile_url)->first();
                if ($userNotification) {
                    $userNotification->delete();
                }
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'rejectedSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();

                return response(['status' => TRUE, 'msg' => 'rejectedSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => FALSE, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function acceptFriendRequest() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "acceptFriendRequest"]);
        
        if (isset($this->requestData['profile_url'])) {
            $userid = $this->requestData['profile_url'];
            $friendMongo = \OlaHub\UserPortal\Models\UserMongo::where('profile_url', $userid)->first();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
            if ($userMongo && $friendMongo) {
                $friendID = $friendMongo->user_id;
                $friends = $userMongo->friends;
                $requests = $userMongo->requests;
                $response = $userMongo->responses;
                if (in_array($friendID, $friends) || in_array($friendID, $requests)) {
                    $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500]]);
                    $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'userAlreadyList', 'code' => 500], 200);
                }
                $friendMongo->pull('requests', app('session')->get('tempID'));
                $userMongo->pull('responses', $friendID);
                $friendMongo->push('friends', app('session')->get('tempID'));
                $userMongo->push('friends', $friendID);

                $userData = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
                $notification = new \OlaHub\UserPortal\Models\NotificationMongo();
                $notification->type = 'user';
                $notification->content = "notifi_acceptFriend";
                $notification->user_name = $userData->first_name . " " . $userData->last_name;
                $notification->profile_url = $userData->profile_url;
                $notification->avatar_url = $userData->profile_picture;
                $notification->read = 0;
                $notification->for_user = $friendMongo->user_id;
                $notification->save();

                $userNotification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', (int) app('session')->get('tempID'))->where('type', 'user')->where('profile_url', $friendMongo->profile_url)->first();
                if ($userNotification) {
                    $userNotification->delete();
                }
                $log->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'acceptedSuccessfully', 'code' => 200]]);
                $log->saveLogSessionData();
        
                return response(['status' => TRUE, 'msg' => 'acceptedSuccessfully', 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => FALSE, 'message' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        response(['status' => FALSE, 'message' => 'NoData', 'code' => 204], 200);
    }

    public function removeFriend() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Profile", 'function_name' => "acceptFriendRequest"]);
        
        if (isset($this->requestData['user_id'])) {
            $userid = $this->requestData['user_id'];
            $friendMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userid)->first();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
            if ($userMongo && $friendMongo) {
                $friendID = $friendMongo->user_id;
                $friends = $userMongo->friends;
                if (!in_array($friendID, $friends)) {
                   $log->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'YouAreNotFriend', 'code' => 500]]);
                   $log->saveLogSessionData();
                    return response(['status' => FALSE, 'msg' => 'YouAreNotFriend', 'code' => 500], 200);
                }
                $friendMongo->pull('friends', app('session')->get('tempID'));
                $userMongo->pull('friends', $friendID);
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
