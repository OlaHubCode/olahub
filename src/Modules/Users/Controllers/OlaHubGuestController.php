<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserModel;

class OlaHubGuestController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $requestCart;
    protected $userAgent;
    protected $ipInfo;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->ipInfo = (new \OlaHub\UserPortal\Helpers\UserHelper)->getIPInfo();
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->requestCart = $return['requestCart'];
        if ($request->header('uniquenum')) {
            $this->userAgent = $request->header('uniquenum');
        } else {
            $this->userAgent = $request->header('user-agent');
        }
    }

    /*
     * Register functions
     */

    function registerUser()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "registerUser"]);

        $validation = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(UserModel::$columnsMaping, (array) $this->requestData);
        // $this->requestData['userPhoneNumber'] = str_replace("+", "00", $this->requestData['userPhoneNumber']);
        if (isset($validation['status']) && !$validation['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validation['data']], 200);
        }
        $is_phone = is_numeric($this->requestData['userEmail']);
        if (!(new \OlaHub\UserPortal\Helpers\UserHelper)->checkUnique($this->requestData['userEmail'], $this->requestData['userCountry'], $is_phone)) {
            if ($is_phone) {
                return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]], 200);
            } else {
                return response(['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['userEmail' => ['validation.unique.email']]], 200);
            }
        }

        // if (!(new \OlaHub\UserPortal\Helpers\UserHelper)->checkUnique($this->requestData['userPhoneNumber'])) {
        //     return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]], 200);
        // }
        $request = $this->requestData;
        $checkInvitation = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($request) {
            $q->where('email', $request['userEmail'])->whereNull('mobile_no');
        })->orWhere(function ($q) use ($request) {
            $q->whereNull('email')->where('mobile_no', $request['userEmail']);
        })->where("invited_by", ">", 0)->first();
        if ($checkInvitation) {
            $userData = $checkInvitation;
            $userData->invitation_accepted_date = date('Y-m-d');
        } else {
            $userData = new UserModel;
        }
        foreach ($this->requestData as $input => $value) {
            if (isset(UserModel::$columnsMaping[$input])) {
                if ($input == 'userEmail' && is_numeric($value)) {
                    $input = 'userPhoneNumber';
                }
                $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value ? $value : null;
            }
        }
        // $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->ipInfo->country)->first();
        // $userData->country_id = $country->id;
        $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
        // print_r($userData); return "";
        $userData->save();

        $userMongo = new \OlaHub\UserPortal\Models\UserMongo;
        $userMongo->user_id = (int) $userData->id;
        $userMongo->username = "$userData->first_name $userData->last_name";
        $userMongo->avatar_url = $userData->profile_picture;
        $userMongo->country_id = $userData->country_id;
        // $userMongo->country_id = app('session')->get('def_country')->id;
        $userMongo->gender = $userData->user_gender;
        $userMongo->profile_url = $userData->profile_url;
        $userMongo->cover_photo = $userData->cover_photo;
        $userMongo->my_groups = [];
        $userMongo->groups = [];
        $userMongo->celebrations = [];
        $userMongo->friends = [];
        $userMongo->requests = [];
        $userMongo->responses = [];
        $userMongo->followed_brands = [];
        $userMongo->followed_occassions = [];
        $userMongo->followed_designers = [];
        $userMongo->followed_interests = [];
        $userMongo->intersts = $this->requestData['userInterests'];
        $userMongo->save();

        $log->setLogSessionData(['user_id' => $userData->id]);

        \OlaHub\UserPortal\Models\Interests::whereIn('interest_id', $this->requestData['userInterests'])->push('users', $userData->id, true);
        if ($userData->mobile_no && $userData->email) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNewUser($userData, $userData->activation_code);
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNewUser($userData, $userData->activation_code);
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200], 200);
        } else if ($userData->mobile_no) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNewUser($userData, $userData->activation_code);
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200], 200);
        } else if ($userData->email) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNewUser($userData, $userData->activation_code);
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200], 200);
        }
    }

    /*
     * Login Functions
     */

    function login()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "login"]);

        if (!isset($this->requestData["userEmail"]) || !isset($this->requestData["userPassword"])) {
            return response(['status' => false, 'msg' => 'rightEmailPassword', 'code' => 406, 'errorData' => []], 200);
        }
        $type = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
        $userData = UserModel::where("email", $this->requestData["userEmail"])->orWhere('mobile_no', $this->requestData["userEmail"])->first();
        if (!$userData) {
            $tempUser = UserModel::withOutGlobalScope('notTemp')->where("email", $this->requestData["userEmail"])->orWhere('mobile_no', $this->requestData["userEmail"])->first();
            if (!$tempUser) {
                if ($type == "phoneNumber") {
                    return response(['status' => false, 'msg' => 'invalidPhonenumber', 'code' => 404], 200);
                } elseif ($type == "email") {
                    return response(['status' => false, 'msg' => 'invalidEmail', 'code' => 404], 200);
                }
                return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 404], 200);
            }
            $userData = $tempUser;
            $userData->invitation_accepted_date = date('Y-m-d');
            $userData->save();

            $log->setLogSessionData(['user_id' => $userData->id]);

            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userData->id)->first();
            if (!$userMongo) {
                $userMongo = new \OlaHub\UserPortal\Models\UserMongo;
            }

            $userMongo->user_id = (int) $userData->id;
            $userMongo->username = "$userData->first_name $userData->last_name";
            $userMongo->avatar_url = $userData->profile_picture;
            $userMongo->country_id = app('session')->get('def_country')->id;
            $userMongo->gender = $userData->user_gender;
            $userMongo->profile_url = $userData->profile_url;
            $userMongo->cover_photo = $userData->cover_photo;
            $userMongo->my_groups = [];
            $userMongo->groups = [];
            $userMongo->celebrations = [];
            $userMongo->friends = [];
            $userMongo->requests = [];
            $userMongo->responses = [];
            $userMongo->intersts = [];
            $userMongo->followed_brands = [];
            $userMongo->followed_occassions = [];
            $userMongo->followed_designers = [];
            $userMongo->followed_interests = [];
            $userMongo->save();
        }

        $userFirstLogin = false;


        if (($userData->facebook_id || $userData->google_id) && empty($userData->password)) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'socialAccount', 'code' => 500]]);
            $log->saveLogSessionData();

            return response(['status' => false, 'msg' => 'socialAccount', 'code' => 500], 200);
        }

        if ($userData->reset_pass_token && $userData->reset_pass_code) {
            $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'accountPasswordReset', 'code' => 510]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'msg' => 'accountPasswordReset', 'code' => 510], 200);
        }

        if (empty($userData->password) && isset($userData->old_password) && $userData->old_password && strlen($userData->old_password) > 5) {
            $status = (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchOldPasswordHash($this->requestData["userPassword"], $userData->old_password);
            if ($status) {
                $userData->password = $this->requestData["userPassword"];
                $userData->old_password = NULL;
                $userData->save();
            }
        } else {
            $status = (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($this->requestData["userPassword"], $userData->password);
        }

        if (!$status) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidPassword', 'code' => 204]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'invalidPassword', 'code' => 204], 200);
        }

        $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent);
        if ($userData->is_first_login) {
            $userData->is_active = 1;
            $userData->save();
            $userFirstLogin = true;
            $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
        }
        $returnUserToSecure = array(
            'country_id' => $userData->country_id,
            'username' => "$userData->first_name $userData->last_name",
            'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
        );

        if (!isset($userData->is_active) || !$userData->is_active) {
            if ($userData->mobile_no && $userData->email) {
                $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200], 200);
            } else if ($userData->mobile_no) {
                $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200], 200);
            } else if ($userData->email) {
                $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200], 200);
            }
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'accountNotActive', 'code' => 500]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'accountNotActive', 'code' => 500], 200);
        }

        if ($checkUserSession && $checkUserSession->status == 1) {
            $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            app('session')->put('tempData', $userData);
            $return = ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200];
            if ($userFirstLogin) {
                $return["userFirstLogin"] = "1";
            }
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();

            return response($return, 200);
        }
        $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createNotActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
        if ($userData->email == $this->requestData["userEmail"]) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $userSession->activation_code);
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200]]);
            $log->saveLogSessionData();

            return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200], 200);
        }
        if ($userData->mobile_no == $this->requestData["userEmail"]) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $userSession->activation_code);
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200]]);
            $log->saveLogSessionData();

            return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200], 200);
        }
    }

    function loginAsUser($id)
    {

        $userData = UserModel::where("id", $id)->first();
        if (!$userData) {
            $tempUser = UserModel::withOutGlobalScope('notTemp')->where("id", $id)->first();
            if (!$tempUser) {
                if ($type == "phoneNumber") {
                    return response(['status' => false, 'msg' => 'invalidPhonenumber', 'code' => 404], 200);
                } elseif ($type == "email") {
                    return response(['status' => false, 'msg' => 'invalidEmail', 'code' => 404], 200);
                }
                return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 404], 200);
            }
        }
        $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

        if ($checkUserSession && $checkUserSession->status == 1) {
            app('session')->put('tempData', $userData);
            $return = ['status' => true, 'logged' => true, 'token' => $checkUserSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200];
            return response($return, 200);
        }
    }

    function loginWithFacebook()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "loginWithFacebook"]);

        $newUser = false;
        if (isset($this->requestData["userEmail"])) {
            $userData = UserModel::where("email", $this->requestData["userEmail"])
                ->first();
            if (!$userData) {
                $userData = UserModel::Where('mobile_no', 'LIKE', "%" . $this->requestData["userEmail"])
                    ->first();
                if (!$userData) {
                    $userData = UserModel::where('facebook_id', $this->requestData["userFacebook"])
                        ->first();
                    if (!$userData) {
                        $newUser = true;
                        $userData = new UserModel;
                        foreach ($this->requestData as $input => $value) {
                            if (isset(UserModel::$columnsMaping[$input])) {
                                $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value;
                            }
                        }
                        if (isset($this->requestData['userCountry']) && $this->requestData['userCountry']) {
                            $userData->country_id = $this->requestData['userCountry'];
                        }
                        $userData->is_active = 1;
                    }
                }
            }
        } else {
            $userData = UserModel::where('facebook_id', $this->requestData["userFacebook"])
                ->first();
            if (!$userData) {
                $newUser = true;
                $userData = new UserModel;
                foreach ($this->requestData as $input => $value) {
                    if (isset(UserModel::$columnsMaping[$input])) {
                        $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value;
                    }
                }
                if (isset($this->requestData['userCountry']) && $this->requestData['userCountry']) {
                    $userData->country_id = $this->requestData['userCountry'];
                }
                $userData->is_active = 1;
            }
        }

        $userData->facebook_id = $this->requestData["userFacebook"];
        if ($userData->save()) {
            $log->setLogSessionData(['user_id' => $userData->id]);

            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userData->id)->first();
            if (!$userMongo) {
                $userMongo = new \OlaHub\UserPortal\Models\UserMongo;
                $userMongo->my_groups = [];
                $userMongo->groups = [];
                $userMongo->celebrations = [];
                $userMongo->friends = [];
                $userMongo->requests = [];
                $userMongo->responses = [];
                $userMongo->intersts = [];
                $userMongo->followed_brands = [];
                $userMongo->followed_occassions = [];
                $userMongo->followed_designers = [];
                $userMongo->followed_interests = [];
            }
            $userMongo->user_id = (int) $userData->id;
            $userMongo->username = "$userData->first_name $userData->last_name";
            $userMongo->avatar_url = $userData->profile_picture;
            $userMongo->country_id = app('session')->get('def_country')->id;
            $userMongo->gender = $userData->user_gender;
            $userMongo->profile_url = $userData->profile_url;
            $userMongo->cover_photo = $userData->cover_photo;
            $userMongo->save();

            $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent, $this->requestCart);
            $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            app('session')->put('tempData', $userData);
            $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'loginWithFacebook', $this->userAgent);

            return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

    function loginWithGoogle()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "loginWithGoogle"]);

        $userData = UserModel::where("email", $this->requestData["userEmail"])
            ->orWhere('google_id', $this->requestData["userGoogle"])
            ->first();
        if (!$userData) {
            $userData = new UserModel;
            foreach ($this->requestData as $input => $value) {
                $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value;
            }
        }
        $userData->google_id = $this->requestData["userGoogle"];
        if ($userData->save()) {
            $log->setLogSessionData(['user_id' => $userData->id]);

            $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent, $this->requestCart);
            if ($checkUserSession && $checkUserSession->status == 1) {
                $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                app('session')->put('tempData', $userData);
                $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'loginWithGoogle', $this->userAgent);

                return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
            }
            $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createNotActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            if ($userData->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $userSession->activation_code);
                $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200]]);
                $log->saveLogSessionData();
                return response(['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

    /*
     * Activation functions 
     */

    function resendActivationCode()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "resendActivationCode"]);

        $userPhoneNumber = false;
        $userEmail = false;
        if (isset($this->requestData["userPhoneNumber"])) {
            $userPhoneNumber = $this->requestData["userPhoneNumber"];
        }
        if (isset($this->requestData["userEmail"])) {
            $userEmail = $this->requestData["userEmail"];
        }
        $country_id = $this->requestData["userCountry"];
        // $userPhoneNumber = str_replace("+", "00", $userPhoneNumber);
        $emailType = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($userEmail);
        $phoneType = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($userPhoneNumber);
        if ($emailType || $phoneType) {
            $email = $userEmail;
            $mobile = $userPhoneNumber;
            if ($emailType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where('email', $email);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($emailType == 'phoneNumber') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $email);
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($phoneType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where('email', $mobile);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($phoneType == 'phoneNumber') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $mobile);
                    $q->where('country_id', $country_id);
                })->first();
            }

            if ($userData) {
                $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                $userData->save();

                if ($userData->mobile_no && $userData->email) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200], 200);
                } else if ($userData->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200], 200);
                } else if ($userData->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200], 200);
                }
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []], 200);
    }

    function resendSecureCode()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "resendSecureCode"]);

        if (isset($this->requestData["userEmail"]) && !empty($this->requestData["userEmail"])) {
            // if (substr($this->requestData["userEmail"], 0, 5) !== "00962" && substr($this->requestData["userEmail"], 0, 1) === "0") {
            //     $phoneTemp = str_split($this->requestData["userEmail"], 1);
            //     unset($phoneTemp[0]);
            //     $this->requestData["userEmail"] = implode("", $phoneTemp);
            // }
            $requestEmailData = $this->requestData["userEmail"];
            $country_id = $this->requestData["userCountry"];
            $type = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
            $userData = false;
            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where('email', $requestEmailData);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->where('is_active', '1')->first();
            } elseif ($type == 'phoneNumber') {
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $requestEmailData);
                    $q->where('country_id', $country_id);
                })->where('is_active', '1')->first();
            }

            if ($userData) {
                $returnUserToSecure = array(
                    'country_id' => $userData->country_id,
                    'username' => "$userData->first_name $userData->last_name",
                    'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
                );
                $checkSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent, $this->requestCart);
                $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createNotActiveSession($checkSession, $userData, $this->userAgent, $this->requestCart);
                if ($type == "email") {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $userSession->activation_code);
                } elseif ($type == "phoneNumber") {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $userSession->activation_code);
                }
                $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'type' => $type, 'token' => false, 'code' => 200]]);
                $log->saveLogSessionData();

                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'type' => $type, 'token' => false, 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []], 200);
    }

    function forgetPasswordUser()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "forgetPasswordUser"]);

        if (isset($this->requestData["userEmail"]) && $this->requestData["userEmail"]) {
            $requestEmailData = str_replace("+", "00", $this->requestData["userEmail"]);
            $type = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
            $userData = false;
            $password = false;
            $tempCode = false;
            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData) {
                    $q->where('email', $requestEmailData);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                })->first();
                $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                $tempCode = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, "num");
            } elseif ($type == 'phoneNumber') {
                $userData = UserModel::where(function ($q) use ($requestEmailData) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $requestEmailData);
                })->first();
                $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                $tempCode = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, "num");
            }

            if ($userData && $password && $tempCode) {
                if (($userData->facebook_id || $userData->google_id) && empty($userData->password)) {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'socialAccount', 'code' => 500]]);
                    $log->saveLogSessionData();

                    return response(['status' => false, 'msg' => 'socialAccount', 'code' => 500], 200);
                }
                $tokenCode = (new \OlaHub\UserPortal\Helpers\SecureHelper)->setPasswordHashing($password);
                $userData->reset_pass_token = md5($tokenCode);
                $userData->reset_pass_code = $tempCode;
                $userData->is_first_login = '0';
                $userData->password = md5($tokenCode);
                $userData->save();
                if ($type == "email") {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendForgetPassword($userData);
                    $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'resetPasswordEmail', 'code' => 200]]);
                    $log->saveLogSessionData();

                    return ['status' => true, 'msg' => 'resetPasswordEmail', 'code' => 200];
                } else if ($type == "phoneNumber") {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendForgetPassword($userData);
                    $log->setLogSessionData(['response' => ['status' => true, 'msg' => 'resetPasswordPhone', 'code' => 200]]);
                    $log->saveLogSessionData();

                    return ['status' => true, 'msg' => 'resetPasswordPhone', 'code' => 200];
                }
            }

            if ($type == "phoneNumber") {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidPhonenumber', 'code' => 204]]);
                $log->saveLogSessionData();
                return ['status' => false, 'msg' => 'invalidPhonenumber', 'code' => 204];
            } elseif ($type == "email") {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmail', 'code' => 204]]);
                $log->saveLogSessionData();
                return ['status' => false, 'msg' => 'invalidEmail', 'code' => 204];
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'validation.NoData', 'code' => 204]]);
        $log->saveLogSessionData();

        return ['status' => false, 'msg' => 'validation.NoData', 'code' => 204];
    }

    function resetGuestPassword()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "resetGuestPassword"]);

        $userData = UserModel::where("reset_pass_token", $this->requestData["resetPasswordToken"])
            ->where("reset_pass_code", $this->requestData["userPassword"])->first();
        if (!$userData) {
            $tempUser = UserModel::withOutGlobalScope('notTemp')
                ->where("reset_pass_token", $this->requestData["resetPasswordToken"])
                ->where("reset_pass_code", $this->requestData["userPassword"])
                ->first();
            if (!$tempUser) {
                return response(['status' => false, 'msg' => 'invalidResetCode', 'code' => 404], 200);
            }
            $userData = $tempUser;
            $userData->invitation_accepted_date = date('Y-m-d');
            $userData->save();
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $userData->id)->first();
            if (!$userMongo) {
                $userMongo = new \OlaHub\UserPortal\Models\UserMongo;
            }

            $userMongo->user_id = (int) $userData->id;
            $userMongo->username = "$userData->first_name $userData->last_name";
            $userMongo->avatar_url = $userData->profile_picture;
            $userMongo->country_id = app('session')->get('def_country')->id;
            $userMongo->gender = $userData->user_gender;
            $userMongo->profile_url = $userData->profile_url;
            $userMongo->cover_photo = $userData->cover_photo;
            $userMongo->my_groups = [];
            $userMongo->groups = [];
            $userMongo->celebrations = [];
            $userMongo->friends = [];
            $userMongo->requests = [];
            $userMongo->responses = [];
            $userMongo->intersts = [];
            $userMongo->followed_brands = [];
            $userMongo->followed_occassions = [];
            $userMongo->followed_designers = [];
            $userMongo->followed_interests = [];
            $userMongo->save();
        }

        app("session")->put("tempData", $userData);
        app("session")->put("tempID", $userData->id);

        if (strlen($this->requestData["userNewPassword"]) > 5 && $this->requestData["userNewPassword"] == $this->requestData["userConfPassword"]) {
            $userData->password = $this->requestData["userNewPassword"];
            $userData->is_first_login = '0';
            $userData->reset_pass_token = NULL;
            $userData->reset_pass_code = NULL;
            $userData->save();
            $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent);
            $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

            if ($userData->mobile_no) {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendForgetPasswordConfirmation($userData);
            }
            if ($userData->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendForgetPasswordConfirmation($userData);
            }
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200]]);
            $log->saveLogSessionData();
            return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
        } else {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'PasswordNotCorrect', 'code' => 204]]);
            $log->saveLogSessionData();
            return ['status' => false, 'msg' => 'PasswordNotCorrect', 'code' => 204];
        }
    }

    function checkActiveCode()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "checkActiveCode"]);

        // $this->requestData["userPhoneNumber"] = str_replace("+", "00", $this->requestData["userPhoneNumber"]);
        $emailType = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
        $phoneType = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($this->requestData["userPhoneNumber"]);
        if (isset($this->requestData['userCode']) && $this->requestData['userCode'] && ($emailType || $phoneType)) {
            $email = $this->requestData["userEmail"];
            $mobile = $this->requestData["userPhoneNumber"];
            $country_id = $this->requestData["userCountry"];
            if ($emailType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where('email', $email);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($emailType == 'phoneNumber') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $email);
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($phoneType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where('email', $mobile);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->first();
            } elseif ($phoneType == 'phoneNumber') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $mobile);
                    $q->where('country_id', $country_id);
                })->first();
            }
            if ($userData && (new \OlaHub\UserPortal\Helpers\UserHelper)->checExpireCode($userData)) {
                if ($userData->activation_code != $this->requestData['userCode']) {
                    $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []]]);
                    $log->saveLogSessionData();
                    return response(['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []], 200);
                }
                $userData->is_active = 1;
                $userData->is_email_verified = 1;
                $userData->is_first_login = 0;
                $userData->activation_code = NULL;
                $userData->save();
                $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent);
                $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

                if ($userData->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivated($userData);
                }
                if ($userData->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivated($userData);
                }
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                app('session')->put('tempData', $userData);
                $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'checkActiveCode', $this->userAgent);

                return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []], 200);
    }

    function checkSecureActive()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "checkSecureActive"]);

        if (isset($this->requestData['userEmail']) && $this->requestData['userEmail'] && isset($this->requestData['userCode']) && $this->requestData['userCode']) {
            // if (substr($this->requestData["userEmail"], 0, 5) !== "00962" && substr($this->requestData["userEmail"], 0, 1) === "0") {
            //     $phoneTemp = str_split($this->requestData["userEmail"], 1);
            //     unset($phoneTemp[0]);
            //     $this->requestData["userEmail"] = implode("", $phoneTemp);
            // }
            $requestEmailData = $this->requestData["userEmail"];
            $country_id = $this->requestData["userCountry"];
            $type = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkEmailOrPhoneNumber($requestEmailData);
            $userData = false;
            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where('email', $requestEmailData);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    $q->where('country_id', $country_id);
                })->where('is_active', '1')->first();
            } elseif ($type == 'phoneNumber') {
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $requestEmailData);
                    $q->where('country_id', $country_id);
                })->where('is_active', '1')->first();
            }

            if (!$userData) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []], 200);
            }
            $checkUserSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->checkUserSession($userData, $this->userAgent, $this->requestData['userCode']);

            if ($checkUserSession && (new \OlaHub\UserPortal\Helpers\UserHelper)->checExpireCode($checkUserSession)) {
                $userSession = (new \OlaHub\UserPortal\Helpers\UserHelper)->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
                if ($type == "email") {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivated($userData, $this->userAgent);
                } elseif ($type == "phoneNumber") {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivated($userData, $this->userAgent);
                }
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                app('session')->put('tempData', $userData);
                $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'checkSecureActive', $this->userAgent);

                return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
            }
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();
        return response(['status' => false, 'msg' => 'invalidEmailPhoneCode', 'code' => 406, 'errorData' => []], 200);
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
}
