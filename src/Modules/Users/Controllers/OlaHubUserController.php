<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserModel;
use OlaHub\UserPortal\Models\UserShippingAddressModel;

class OlaHubUserController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $authorization;
    protected $uploadData;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->authorization = $request->header('authorization');
        $this->uploadData = $request->all();
    }

    /*
     * Get user data
     */

    public function getHeaderInfo()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getHeaderInfo"]);

        $user = app('session')->get('tempData');
        if ($user) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
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

    public function getProfileInfo()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getProfileInfo"]);

        $user = app('session')->get('tempData');
        if ($user) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\ProfileInfoResponseHandler');
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

    public function getUserInfo()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getUserInfo"]);

        $user = app('session')->get('tempData');
        if ($user) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\UsersResponseHandler');
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

    public function getUservoucherData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getUservoucherData"]);

        $user = app('session')->get('tempData');
        if ($user) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\UserBalanceDetailsResponseHandler');
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

    public function getUserFriends()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getUserFriends"]);

        $userID = app('session')->get('tempID');
        $celebrationId = null;
        if (isset($this->requestFilter['celebration'])) {
            $celebrationId = $this->requestFilter['celebration'];
        };
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userID)->first();
        if ($userMongo->friends && count($userMongo->friends) > 0) {
            if ($celebrationId != null) {
                $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $celebrationId)->first();
                $friends = \OlaHub\UserPortal\Models\UserMongo::whereIn('user_id', $userMongo->friends)->paginate(10);
                foreach ($friends as $friend) {
                    if ($celebration->user_id != $friend['user_id']) {
                        $part = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', $friend['user_id'])->where('celebration_id', $celebrationId)->first();
                        if ($part) {
                            continue;
                        } else {
                            $return['data'][] = [
                                "profile" => isset($friend->user_id) ? $friend->user_id : 0,
                                "username" => isset($friend->username) ? $friend->username : NULL,
                                "profile_url" => isset($friend->profile_url) ? $friend->profile_url : NULL,
                                "user_gender" => isset($friend->gender) ? $friend->gender : NULL,
                                //                            "country" => isset($country) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name') : NULL,
                                "avatar_url" => isset($friend->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->avatar_url),
                                "cover_photo" => isset($friend->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->cover_photo),
                            ];
                        }
                    }
                }
                //                $friends = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::WhereNotIn('user_id', $friends)->paginate(10);
                //                dd($friends);
            } else {
                $friends = \OlaHub\UserPortal\Models\UserMongo::whereIn('user_id', $userMongo->friends)->paginate(10);
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($friends, '\OlaHub\UserPortal\ResponseHandlers\MyFriendsResponseHandler');
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

    public function getUserRequests()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getUserRequests"]);

        $userID = app('session')->get('tempID');
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userID)->first();
        if ($userMongo->requests && count($userMongo->requests) > 0) {
            $requests = \OlaHub\UserPortal\Models\UserMongo::whereIn('user_id', $userMongo->requests)->get();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($requests, '\OlaHub\UserPortal\ResponseHandlers\MyFriendsResponseHandler');
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

    public function getUserResponses()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getUserResponses"]);

        $userID = app('session')->get('tempID');
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userID)->first();
        if ($userMongo->responses && count($userMongo->responses) > 0) {
            $responses = \OlaHub\UserPortal\Models\UserMongo::whereIn('user_id', $userMongo->responses)->get();
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($responses, '\OlaHub\UserPortal\ResponseHandlers\MyFriendsResponseHandler');
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

    public function updateUserData()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "updateUserData"]);
        if (empty($this->requestData["userPhoneNumber"]) && empty($this->requestData["userEmail"])) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' =>['userEmailPhone' => ['validation.userPhoneEmail']]], 200);
        }

        $validatorUser = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateUpdateUserData(UserModel::$columnsMaping, (array) $this->requestData);
        if (isset($validatorUser['status']) && !$validatorUser['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validatorUser['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validatorUser['data']], 200);
        }
        $validatorAddress = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(UserShippingAddressModel::$columnsMaping, (array) $this->requestData);
        if (isset($validatorAddress['status']) && !$validatorAddress['status']) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validatorAddress['data']]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validatorAddress['data']], 200);
        }
        if (isset($this->requestData['userInterests']) && count($this->requestData['userInterests']) <= 0) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['userInterests' => ['validation.api.interests']]]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => ['userInterests' => ['validation.api.interests']]], 200);
        }
        $userData = app('session')->get('tempData');

        /*** check changes ***/
        if (!empty($this->requestData["userNewPassword"]) && !empty($this->requestData["confirmPassword"])) {
            $confirm = (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($this->requestData["confirmPassword"], $userData->password);
            if (!$confirm) {
                return response(['status' => false, 'msg' => 'password_incorrect'], 200);
            }
        }

        if ((!empty($this->requestData['userPhoneNumber']) && $userData->mobile_no != $this->requestData['userPhoneNumber']) ||
            (!empty($this->requestData['userCountry']) && $userData->country_id != $this->requestData['userCountry'])
        ) {
            $phone = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData['userPhoneNumber']);
            $country_id = $this->requestData["userCountry"];
            $u = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($phone, $country_id) {
                $q->where('mobile_no', $phone);
                $q->where('country_id', $country_id);
                $q->where('for_merchant', 0);
            })->first();
            if ($u) {
                return response(['status' => false, 'msg' => 'phone_exist', 'code' => 406], 200);
            }
            if (!empty($this->requestData["active_code"])) {
                $phone = $userData->mobile_no;
                $country_id = $userData->country_id;
                $code = $this->requestData["active_code"];
                $uc = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($phone, $code, $country_id) {
                    $q->where('mobile_no', $phone);
                    $q->where('country_id', $country_id);
                    $q->where('activation_code', $code);
                })->first();
                if (!$uc) {
                    return response(['status' => false, 'msg' => 'invalid_active_code'], 200);
                }
            } else {
                $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                $userData->save();
                $userData->country_id = $country_id;
                $userData->mobile_no = $phone;
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return response(['status' => true, 'msg' => 'confirm_phone_sent'], 200);
            }
        }

        if (!empty($this->requestData['userEmail']) && $userData->email != $this->requestData['userEmail']) {
            $email = $this->requestData['userEmail'];
            $e = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($email) {
                $q->where('email', $email);
            })->first();
            if ($e) {
                return response(['status' => false, 'msg' => 'email_exist', 'code' => 406], 200);
            }
            if (!empty($this->requestData["active_code"])) {
                $email = $userData->email;
                $code = $this->requestData["active_code"];
                $ec = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($email, $code) {
                    $q->where('email', $email);
                    $q->where('activation_code', $code);
                })->first();
                if (!$ec) {
                    return response(['status' => false, 'msg' => 'invalid_active_code'], 200);
                }
            } else {
                $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                $userData->save();
                $userData->email = $this->requestData['userEmail'];
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return response(['status' => true, 'msg' => 'confirm_email_sent'], 200);
            }
        }

        /********************/

        // $checkChanges = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailPhoneChange($userData, $this->requestData);
        // if (!$checkChanges) {
        //     $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'some data send wrong']]);
        //     $log->saveLogSessionData();
        //     return response(['status' => false, 'msg' => 'some data send wrong'], 200);
        // }
        // $isFirstLogin = false;
        if (!empty($this->requestData['userPhoneNumber']))
            $this->requestData['userPhoneNumber'] = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData['userPhoneNumber']);
        foreach ($this->requestData as $input => $value) {
            if (isset($this->requestData['userNewPassword']) && $this->requestData['userNewPassword'] != "") {
                $userData->password = $this->requestData['userNewPassword'];
                if ($userData->is_first_login == "1") {
                    $userData->is_first_login = "0";
                    // $isFirstLogin = TRUE;
                }
            }
            if (isset(UserModel::$columnsMaping[$input])) {
                $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value;
            }
        }
        $userData->save();
        $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userData->id)->first();

        $userMongo->username = "$userData->first_name $userData->last_name";
        $userMongo->country_id = $userData->country_id;
        $userMongo->gender = $userData->user_gender;
        $userMongo->profile_url = $userData->profile_url;

        $userMongo->intersts = $this->requestData['userInterests'];
        $userMongo->save();
        (new \OlaHub\UserPortal\Helpers\UserShippingAddressHelper)->getUserShippingAddress($userData, $this->requestData);
        // $checkUpdateActivation = (new \OlaHub\UserPortal\Helpers\UserHelper)->sendUpdateActivationCode($userData, $checkChanges);
        // if ($checkUpdateActivation) {
        //     if ($isFirstLogin) {
        //         $checkUpdateActivation["userFirstLogin"] = "0";
        //     }
        //     $log->setLogSessionData(['response' => $checkUpdateActivation]);
        //     $log->saveLogSessionData();
        //     return response($checkUpdateActivation, 200);
        // }
        $user = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\UsersResponseHandler');
        $return = ['user' => $user['data'], 'status' => true, 'msg' => 'updated Account succussfully', 'code' => 200];
        // if ($isFirstLogin) {
        //     $return["userFirstLogin"] = "0";
        // }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    function logoutUser()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "logoutUser"]);

        $sessionData = app('session')->get('tempSession');
        if (isset($sessionData->activation_code) && isset($sessionData->hash_token)) {
            $sessionData->activation_code = null;
            $sessionData->hash_token = null;
            $sessionData->save();
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => false, 'token' => false, 'code' => 200]]);
            $log->saveLogSessionData();
            return ['status' => true, 'logged' => false, 'token' => false, 'code' => 200];
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'Wrong data sent', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();
        return ['status' => false, 'msg' => 'Wrong data sent', 'code' => 406, 'errorData' => []];
    }

    public function uploadUserProfilePhoto()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "uploadUserProfilePhoto"]);

        $this->requestData = isset($this->uploadData) ? $this->uploadData : [];
        if (count($this->requestData) > 0 && $this->requestData['userProfilePicture']) {
            $user = app('session')->get('tempData');
            $imagePath = (new \OlaHub\UserPortal\Helpers\UserHelper)->uploadUserImage($user, 'profile_picture', $this->requestData['userProfilePicture']);
            $user->profile_picture = $imagePath;
            $saved = $user->save();
            if ($saved) {
                $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
                $userMongo->avatar_url = $imagePath;
                $userMongo->save();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
                $return["status"] = TRUE;
                $return["code"] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function uploadUserCoverPhoto()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "uploadUserCoverPhoto"]);

        $this->requestData = isset($this->uploadData) ? $this->uploadData : [];
        if (count($this->requestData) > 0 && $this->requestData['userCoverPhoto']) {
            $user = app('session')->get('tempData');
            $imagePath = (new \OlaHub\UserPortal\Helpers\UserHelper)->uploadUserImage($user, 'cover_photo', $this->requestData['userCoverPhoto']);
            $user->cover_photo = $imagePath;
            $saved = $user->save();
            if ($saved) {
                $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
                $userMongo->cover_photo = $imagePath;
                $userMongo->save();
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
                $return["status"] = TRUE;
                $return["code"] = 200;
                $log->setLogSessionData(['response' => $return]);
                $log->saveLogSessionData();
                return response($return, 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getAllInterests()
    {
        $interests = \OlaHub\UserPortal\Models\Interests::withoutGlobalScope('interestsCountry')->get();
        if ($interests->count() < 1) {
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($interests, '\OlaHub\UserPortal\ResponseHandlers\InterestsForPrequestFormsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog("", $return, 'getAllInterests', $this->userAgent);
        return response($return, 200);
    }

    public function setupTwoStep()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "setupTwoStep"]);
        $method = $this->requestData["method"];
        $status = $this->requestData["status"];
        $code = isset($this->requestData["userCode"]) ? $this->requestData["userCode"] : NULL;
        $userData = app('session')->get('tempData');
        $two_step = $this->getTwoStep($method, $status, $userData->two_step);

        if (empty($method)) {
            return response(['status' => false, 'msg' => 'invalid_method'], 200);
        }

        if (empty($status)) {
            $userData->two_step = $two_step;
            $userData->save();
            return response(['status' => true, 'two_step' => $two_step, 'msg' => 'twostep_' . $method . '_disabled'], 200);
        } else {
            if (!empty($code)) {
                if ($userData->activation_code != $code)
                    return response(['status' => false, 'msg' => 'invalidCode'], 200);
                else {
                    $userData->two_step = $two_step;
                    $userData->save();
                    return response(['status' => true, 'two_step' => $two_step, 'msg' => 'twostep_' . $method . '_enabled'], 200);
                }
            }
            $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
            $userData->save();
            if ($method == 'phone') {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return response(['status' => true, 'msg' => 'confirm_phone_sent'], 200);
            } else if ($method == 'email') {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return response(['status' => true, 'msg' => 'confirm_email_sent'], 200);
            }
        }
    }

    function getTwoStep($method, $status = false, $twostep)
    {
        if ($status) {
            if ($method == 'phone' && !$twostep) {
                return 1;
            } else if ($method == 'phone' && $twostep) {
                return 3;
            }
            if ($method == 'email' && !$twostep) {
                return 2;
            } else if ($method == 'email' && $twostep) {
                return 3;
            }
        } else {
            if ($method == 'phone' && $twostep == 1) {
                return 0;
            } else if ($method == 'phone' && $twostep == 3) {
                return 2;
            }
            if ($method == 'email' && $twostep == 2) {
                return 0;
            } else if ($method == 'email' && $twostep == 3) {
                return 1;
            }
        }
    }

    public function authorizedLogins()
    {
        $return['data'] =  \OlaHub\UserPortal\Models\UserLoginsModel::selectRaw('device_id, datetime, location, device_platform, device_model, status')
            ->where('user_id', app('session')->get('tempID'))->where('deleted', 0)->get();
        $return['status'] = true;
        $return['code'] = 200;
        return response($return, 200);
    }

    public function authorizedRemove()
    {
        $deviceId = $this->requestData["deviceId"];
        if (empty($deviceId))
            return response(['status' => false, 'msg' => 'invalid_devide_id'], 200);
        $deleted = \OlaHub\UserPortal\Models\UserLoginsModel::where('user_id', app('session')->get('tempID'))->where('device_id', $deviceId)
            ->update(
                array(
                    'code' => NULL,
                    'status' => 0,
                    'deleted' => 1
                )
            );
        $return['status'] = $deleted;
        $return['code'] = 200;
        return response($return, 200);
    }
}
