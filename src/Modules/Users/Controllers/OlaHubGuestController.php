<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserModel;
use OlaHub\UserPortal\Helpers\UserHelper;
use Illuminate\Support\Facades\Crypt;
use OlaHub\UserPortal\Models\UserSubscribe;
use OlaHub\UserPortal\Models\UsersReferenceCodeUsedModel;

class OlaHubGuestController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $requestCart;
    protected $userAgent;
    protected $userHelper;
    protected $ipInfo;
    protected $lang;
    protected $allData;

    public function __construct(Request $request)
    {
        $this->allData = $request->all();
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->ipInfo = UserHelper::getIPInfo();
        $this->userHelper = new UserHelper;
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->requestCart = $return['requestCart'];
        $this->lang = $request->header('language');
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
        $this->requestData['userPassword'] = json_decode(Crypt::decrypt($this->requestData['userPassword'], false));
        $this->requestData['userInterests'] = implode(",", $this->requestData['userInterests']);
        // var_dump($this->requestData);return '';
        $validation = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(UserModel::$columnsMaping, (array) $this->requestData);
        // $this->requestData['userPhoneNumber'] = str_replace("+", "00", $this->requestData['userPhoneNumber']);
        if (isset($validation['status']) && !$validation['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validation['data']], 200);
        }
        $is_phone = is_numeric($this->requestData['userEmail']);
        if (!$this->userHelper->checkUnique($this->requestData['userEmail'], $this->requestData['userCountry'], $is_phone)) {
            if ($is_phone) {
                return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]], 200);
            } else {
                return response(['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['userEmail' => ['validation.unique.email']]], 200);
            }
        }
        if (!empty($this->requestData['refCode'])) {
            $checkRefCode = UserModel::checkReferenceCodeUser($this->requestData['refCode'], 'register');
            if ($checkRefCode === "notBegin") {
                return response(['status' => false, 'msg' => 'notBegin', 'code' => 406], 200);
            } elseif ($checkRefCode === "expired") {
                return response(['status' => false, 'msg' => 'refCodeExpired', 'code' => 406], 200);
            } elseif ($checkRefCode === false) {
                return response(['status' => false, 'msg' => 'refCodeNotFound', 'code' => 406], 200);
            }
        }

        // if (!$this->userHelper->checkUnique($this->requestData['userPhoneNumber'])) {
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
        if (!isset($request['userCountry'])) {
            $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', @$this->ipInfo->country_code)->first();
            $userData->country_id = @$country->id;
        }
        $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
        $userData->save();
        if (!empty($this->requestData['refCode'])) {
            $codeRefUsed = new UsersReferenceCodeUsedModel;
            $codeRefUsed->code_id = $checkRefCode;
            $codeRefUsed->user_id = $userData->id;
            $codeRefUsed->save();
        }

        $log->setLogSessionData(['user_id' => $userData->id]);
        $this->requestData["deviceID"] = empty($this->requestData['deviceID']) ? $this->userHelper->getDeviceID() : $this->requestData["deviceID"];
        $this->userHelper->addUserLogin($this->requestData, $userData->id, true);

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

        $log = new \OlaHub\UserPortal\Helpers\Logs();
        // $log->setLogSessionData(['module_name' => "Users", 'function_name' => "login"]);

        // $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        // $log->setLogSessionData(['module_name' => "Users", 'function_name' => "login"]);

        //        $this->requestData = (array) json_decode(Crypt::decrypt($this->requestData, false));

        if (env('REQUEST_TYPE') != 'postMan') {
            $this->requestData = (array) json_decode(Crypt::decrypt($this->requestData, false));
        }

        if (!isset($this->requestData["userEmail"])) {
            return response(['status' => false, 'msg' => 'rightEmailPhone', 'code' => 406, 'errorData' => []], 200);
        }

        $type = $this->userHelper->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
        $this->requestData["deviceID"] = empty($this->requestData['deviceID']) ? $this->userHelper->getDeviceID() : $this->requestData["deviceID"];
        $country_id = $this->requestData["userCountry"];
        $emailPhone = $this->requestData["userEmail"];
        $userData = NULL;

        // $allCountries = \OlaHub\UserPortal\Models\ShippingCountries::selectRaw("countries.name as text, countries.id as value, phonecode")
        //     ->join('countries', 'countries.id', 'shipping_countries.olahub_country_id')
        //     ->orderBy('shipping_countries.name', 'asc')->get();
        // foreach ($allCountries as $country) {
        //     $country->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'text');
        // }

        // check if email or phone with ip country
        if ($type == 'email') {
            $userData = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($emailPhone) {
                $q->where('email', $emailPhone);
            })->first();
        } elseif ($type == 'phoneNumber') {
            $emailPhone = $this->userHelper->fullPhone($emailPhone);
            $userData = UserModel::withOutGlobalScope('notTemp')->where(function ($q) use ($emailPhone, $country_id) {
                $q->where('mobile_no', $emailPhone);
                $q->where('country_id', $country_id);
                $q->where('for_merchant', 0);
            })->first();
        }

        // if ($userData && empty($this->requestData["userPassword"]) && empty($this->requestData["useSMS"])) {
        //     return ['status' => false, 'msg' => 'accountChecked', 'code' => 204, 'countries' => $allCountries];
        // }
        if (!$userData) {
            // check if there phones to other country
            // $usersData = UserModel::withOutGlobalScope('notTemp')->where('mobile_no', $emailPhone)->get();
            // if ($usersData->count() && !isset($this->requestData["userCountry"])) {
            //     return ['status' => false, 'msg' => 'multiNumbers', 'code' => 204, 'countries' => $allCountries];
            // }
            if ($type == "phoneNumber") {
                return response(['status' => false, 'msg' => 'invalidPhonenumber', 'code' => 404], 200);
            } elseif ($type == "email") {
                return response(['status' => false, 'msg' => 'invalidEmail', 'code' => 404], 200);
            }
            return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 404], 200);
        }

        if ($userData && !empty($this->requestData["useSMS"]) && empty($this->requestData["userPassword"])) {
            $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
            $userData->save();
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
            return ['status' => false, 'msg' => 'sent_sms_login', 'code' => 204];
        } else if ($userData && !empty($this->requestData["useSMS"]) && !empty($this->requestData["userPassword"])) {
            if ($userData->activation_code != $this->requestData['userPassword']) {
                return response(['status' => false, 'msg' => 'invalidSMSCode', 'code' => 406, 'errorData' => []], 200);
            } else {
                $userData->activation_code = NULL;
                $userData->save();
            }
        }

        if (!empty($userData->invited_by) && empty($userData->invitation_accepted_date)) {
            $userData->invitation_accepted_date = date('Y-m-d');
            $userData->save();
            // $log->setLogSessionData(['user_id' => $userData->id]);
        }

        $userFirstLogin = false;

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

        if (!$status && empty($this->requestData["useSMS"])) {
            // $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidPassword', 'code' => 204]]);
            // $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'invalidPassword', 'code' => 204], 200);
        }
        $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent);
        if ($userData->is_first_login) {
            $userData->is_active = 1;
            $userData->save();
            $userFirstLogin = true;
            $checkUserSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
        }
        $returnUserToSecure = array(
            'country_id' => $userData->country_id,
            'username' => "$userData->first_name $userData->last_name",
            'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
        );

        if (!isset($userData->is_active) || !$userData->is_active) {
            if ($userData->mobile_no && $userData->email) {
                // $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200]]);
                // $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "activationCodePhoneEmail", 'code' => 200], 200);
            } else if ($userData->mobile_no) {
                // $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200]]);
                // $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodePhone", 'code' => 200], 200);
            } else if ($userData->email) {
                // $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200]]);
                // $log->saveLogSessionData();
                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'new', 'token' => false, 'msg' => "apiActivationCodeEmail", 'code' => 200], 200);
            }
            // $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'accountNotActive', 'code' => 500]]);
            // $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'accountNotActive', 'code' => 500], 200);
        }

        // two-step
        $logged = $this->userHelper->checkUserLogin($userData->id, $this->requestData['deviceID']);
        $twostep = false;
        $status = 1;
        $code = NULL;
        if ($logged && $logged->status) {
            $this->userHelper->addUserLogin($this->requestData, $userData->id, true);
        } else {
            if ($userData->two_step) {
                $twostep = true;
                $status = 0;
                $code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
            }
            $this->userHelper->addUserLogin($this->requestData, $userData->id, $status, $code);
        }
        if ($twostep) {
            if ($userData->email == $this->requestData["userEmail"]) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                // $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200]]);
                // $log->saveLogSessionData();

                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200], 200);
            }
            if ($userData->mobile_no == $this->requestData["userEmail"]) {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                // $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200]]);
                // $log->saveLogSessionData();

                return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200], 200);
            }
        }

        $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
        app('session')->put('tempData', $userData);
        $u = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
        $return = ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => Crypt::encrypt(json_encode($u), false), 'code' => 200];
        if ($userFirstLogin) {
            $return["userFirstLogin"] = "1";
        }
        // $log->setLogSessionData(['response' => $return]);
        // $log->saveLogSessionData();
        $log->saveLog($userData->id, $this->requestData, 'login');

        return response($return, 200);
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
        $checkUserSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

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
            $userData = UserModel::where("email", $this->requestData["userEmail"])->first();
            if (!$userData) {
                $userData = UserModel::Where('mobile_no', $this->requestData["userEmail"])->first();
                if (!$userData) {
                    $userData = UserModel::where('facebook_id', $this->requestData["userFacebook"])->first();
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
                        } else {
                            $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', @$this->ipInfo->country_code)->first();
                            $userData->country_id = @$country->id;
                        }
                        $userData->is_active = 1;
                    }
                }
            }
        } else {
            $userData = UserModel::where('facebook_id', $this->requestData["userFacebook"])->first();
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
                } else {
                    $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', @$this->ipInfo->country_code)->first();
                    $userData->country_id = @$country->id;
                }
                $userData->is_active = 1;
            }
        }

        $userData->facebook_id = $this->requestData["userFacebook"];
        if ($userData->save()) {
            $log->setLogSessionData(['user_id' => $userData->id]);

            $returnUserToSecure = array(
                'country_id' => $userData->country_id,
                'username' => "$userData->first_name $userData->last_name",
                'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
            );

            // two-step
            $this->requestData["deviceID"] = empty($this->requestData['deviceID']) ? $this->userHelper->getDeviceID() : $this->requestData["deviceID"];
            $logged = $this->userHelper->checkUserLogin($userData->id, $this->requestData['deviceID']);
            $twostep = false;
            $status = 1;
            $code = NULL;
            if ($logged && $logged->status) {
                $this->userHelper->addUserLogin($this->requestData, $userData->id, true);
            } else {
                if ($userData->two_step) {
                    $twostep = true;
                    $status = 0;
                    $code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                }
                $this->userHelper->addUserLogin($this->requestData, $userData->id, $status, $code);
            }
            if ($twostep) {
                if ($userData->email == $this->requestData["userEmail"]) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200], 200);
                }
                if ($userData->mobile_no == $this->requestData["userEmail"]) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200], 200);
                }
            }

            $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent, $this->requestCart);
            $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            app('session')->put('tempData', $userData);
            $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'loginWithFacebook', $this->userAgent);

            $u = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
            return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => Crypt::encrypt(json_encode($u), false), 'code' => 200], 200);
        }
        $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();

        return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => []], 200);
    }

    function appleBack()
    {
        $data = $this->allData;
        $r = explode(".", $data['id_token']);
        $r = base64_decode($r[1]);
        $email = json_decode($r)->email;
        $data['email'] = !empty($email) ? $email : "";
        $redirectLink = 'userAccess?';
        if (!empty($data['id_token']) && !empty($email)) {
            $user = UserHelper::buildAppleData($data);
            return redirect()->to($redirectLink . json_encode($user));
        }
        return redirect()->to($redirectLink . json_encode(['error' => "could_not_find_token"]));
    }

    function loginWithApple()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "loginWithApple"]);
        if (isset($this->requestData["userEmail"])) {
            $userData = UserModel::where("email", $this->requestData["userEmail"])->first();
            if (!$userData) {
                $userData = new UserModel;
                foreach ($this->requestData as $input => $value) {
                    if (isset(UserModel::$columnsMaping[$input])) {
                        $userData->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(UserModel::$columnsMaping, $input)} = $value;
                    }
                }
                if (isset($this->requestData['userCountry']) && $this->requestData['userCountry']) {
                    $userData->country_id = $this->requestData['userCountry'];
                } else {
                    $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', @$this->ipInfo->country_code)->first();
                    $userData->country_id = @$country->id;
                }
                $userData->is_active = 1;
            }
        }

        if ($userData->save()) {
            $log->setLogSessionData(['user_id' => $userData->id]);

            $returnUserToSecure = array(
                'country_id' => $userData->country_id,
                'username' => "$userData->first_name $userData->last_name",
                'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
            );

            // two-step
            $this->requestData["deviceID"] = empty($this->requestData['deviceID']) ? $this->userHelper->getDeviceID() : $this->requestData["deviceID"];
            $logged = $this->userHelper->checkUserLogin($userData->id, $this->requestData['deviceID']);
            $twostep = false;
            $status = 1;
            $code = NULL;
            if ($logged && $logged->status) {
                $this->userHelper->addUserLogin($this->requestData, $userData->id, true);
            } else {
                if ($userData->two_step) {
                    $twostep = true;
                    $status = 0;
                    $code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                }
                $this->userHelper->addUserLogin($this->requestData, $userData->id, $status, $code);
            }
            if ($twostep) {
                if ($userData->email == $this->requestData["userEmail"]) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "email", 'code' => 200], 200);
                }
                if ($userData->mobile_no == $this->requestData["userEmail"]) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                    $log->setLogSessionData(['response' => ['status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200]]);
                    $log->saveLogSessionData();

                    return response(['user' => $returnUserToSecure, 'status' => true, 'logged' => 'secure', 'token' => false, 'type' => "phoneNumber", 'code' => 200], 200);
                }
            }

            $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent, $this->requestCart);
            $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            app('session')->put('tempData', $userData);
            $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'loginWithFacebook', $this->userAgent);

            $u = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
            return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => Crypt::encrypt(json_encode($u), false), 'code' => 200], 200);
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

            $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent, $this->requestCart);
            if ($checkUserSession && $checkUserSession->status == 1) {
                $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                app('session')->put('tempData', $userData);
                $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'loginWithGoogle', $this->userAgent);

                return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
            }
            $userSession = $this->userHelper->createNotActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
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
            $userPhoneNumber = $this->userHelper->fullPhone($this->requestData["userPhoneNumber"]);
        }
        if (isset($this->requestData["userEmail"])) {
            $userEmail = $this->requestData["userEmail"];
        }
        $country_id = $this->requestData["userCountry"];
        // $userPhoneNumber = str_replace("+", "00", $userPhoneNumber);
        $emailType = $this->userHelper->checkEmailOrPhoneNumber($userEmail);
        $phoneType = $this->userHelper->checkEmailOrPhoneNumber($userPhoneNumber);
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
                $email = $this->userHelper->fullPhone($email);
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $email);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
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
                    $q->where('for_merchant', 0);
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
            $requestEmailData = $this->requestData["userEmail"];
            $country_id = $this->requestData["userCountry"];
            $type = $this->userHelper->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
            $userData = false;
            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData) {
                    $q->where('email', $requestEmailData);
                })->where('is_active', '1')->first();
            } elseif ($type == 'phoneNumber') {
                $requestEmailData = $this->userHelper->fullPhone($requestEmailData);
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where('mobile_no', $requestEmailData);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
                })->where('is_active', '1')->first();
            }

            if ($userData) {
                $returnUserToSecure = array(
                    'country_id' => $userData->country_id,
                    'username' => "$userData->first_name $userData->last_name",
                    'avatar' => ($userData->profile_picture ? STORAGE_URL . "/$userData->profile_picture" : NULL)
                );
                $code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
                $this->userHelper->addUserLogin($this->requestData, $userData->id, false, $code);
                if ($type == "email") {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivation($userData, $this->userAgent, $code);
                } elseif ($type == "phoneNumber") {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivation($userData, $this->userAgent, $code);
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
            $requestEmailData = $this->requestData["userEmail"];
            // $requestEmailData = str_replace("+", "00", $this->requestData["userEmail"]);
            $type = $this->userHelper->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
            $userData = false;
            $password = false;
            $tempCode = false;
            $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->ipInfo->country_code)->first();
            $country_id = isset($this->requestData["userCountry"]) ? $this->requestData["userCountry"] : @$country->id;

            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData) {
                    $q->where('email', $requestEmailData);
                })->first();
            } elseif ($type == 'phoneNumber') {
                $requestEmailData = $this->userHelper->fullPhone($requestEmailData);
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where('mobile_no', $requestEmailData);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
                })->first();
            }
            $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
            $tempCode = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, "num");

            if ($userData && $password && $tempCode) {
                // if (($userData->facebook_id || $userData->google_id) && empty($userData->password)) {
                //     $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'socialAccount', 'code' => 500]]);
                //     $log->saveLogSessionData();

                //     return response(['status' => false, 'msg' => 'socialAccount', 'code' => 500], 200);
                // }
                $tokenCode = (new \OlaHub\UserPortal\Helpers\SecureHelper)->setPasswordHashing($password);
                $userData->reset_pass_token = md5($tokenCode);
                $userData->reset_pass_code = $tempCode;
                // $userData->is_first_login = '0';
                // $userData->password = md5($tokenCode);
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
                $requestEmailData = $this->userHelper->fullPhone($requestEmailData);
                $usersData = UserModel::selectRaw('concat(first_name, " ", last_name) as full_name, country_id, mobile_no, phonecode,
                concat("' . STORAGE_URL . '/", profile_picture) as avatar')
                    ->leftJoin('shipping_countries', 'shipping_countries.olahub_country_id', 'users.country_id')
                    ->where('mobile_no', $requestEmailData)->get();
                if ($usersData->count()) {
                    return ['status' => false, 'msg' => 'multiNumbers', 'code' => 204, 'users' => $usersData];
                }
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

        $this->requestData = (array) json_decode(Crypt::decrypt($this->requestData, false));

        $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', @$this->ipInfo->country_code)->first();
        $country_id = isset($this->requestData["userCountry"]) ? $this->requestData["userCountry"] : @$country->id;
        $type = $this->userHelper->checkEmailOrPhoneNumber($this->requestData["userEmail"]);
        $userData = null;
        $email = $this->requestData["userEmail"];
        $code = $this->requestData["userPassword"];

        if ($type == 'email') {
            $userData = UserModel::where(function ($q) use ($email, $code) {
                $q->where('email', $email);
                $q->where('reset_pass_code', $code);
            })->first();
        } elseif ($type == 'phoneNumber') {
            $email = $this->userHelper->fullPhone($email);
            $userData = UserModel::where(function ($q) use ($email, $code, $country_id) {
                $q->where('mobile_no', $email);
                $q->where('reset_pass_code', $code);
                $q->where('country_id', $country_id);
            })->first();
        }

        if (!$userData) {
            return response(['status' => false, 'msg' => 'invalidResetCode', 'code' => 404], 200);
        }

        app("session")->put("tempData", $userData);
        app("session")->put("tempID", $userData->id);

        if (strlen($this->requestData["userNewPassword"]) > 5 && $this->requestData["userNewPassword"] == $this->requestData["userConfPassword"]) {
            $userData->password = $this->requestData["userNewPassword"];
            $userData->is_first_login = '0';
            $userData->reset_pass_token = NULL;
            $userData->reset_pass_code = NULL;
            $userData->save();
            $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent);
            $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

            if ($userData->mobile_no) {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendForgetPasswordConfirmation($userData);
            }
            if ($userData->email) {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendForgetPasswordConfirmation($userData);
            }
            $log->setLogSessionData(['response' => ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200]]);
            $log->saveLogSessionData();
            $u = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
            return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' =>  Crypt::encrypt(json_encode($u), false), 'code' => 200], 200);
        } else {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'PasswordNotCorrect', 'code' => 204]]);
            $log->saveLogSessionData();
            return ['status' => false, 'msg' => 'PasswordNotCorrect', 'code' => 204];
        }
    }
   public function subscribe()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "getHeaderInfo"]);
        if (!empty($this->requestData['email'])) {

        $subscribe = new UserSubscribe();
        $subscribe->email = $this->requestData['email'];
        $subscribe->save();
        return response(['status' => true, 'msg' => 'successSubscribe', 'code' => 200], 200);

        }else{
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);

        }
    }
    function checkActiveCode()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Users", 'function_name' => "checkActiveCode"]);

        // $this->requestData["userPhoneNumber"] = str_replace("+", "00", $this->requestData["userPhoneNumber"]);
        $emailType = $this->userHelper->checkEmailOrPhoneNumber(@$this->requestData["userEmail"]);
        $phoneType = $this->userHelper->checkEmailOrPhoneNumber(@$this->requestData["userPhoneNumber"]);
        if (isset($this->requestData['userCode']) && $this->requestData['userCode'] && ($emailType || $phoneType)) {
            $email = @$this->requestData["userEmail"];
            $mobile = @$this->requestData["userPhoneNumber"];
            $country_id = $this->requestData["userCountry"];
            if ($emailType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where('email', $email);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    // $q->where('country_id', $country_id);
                })->first();
            } elseif ($emailType == 'phoneNumber') {
                $email = $this->userHelper->fullPhone($email);
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($email, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $email);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
                })->first();
            } elseif ($phoneType == 'email') {
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where('email', $mobile);
                    $q->where(function ($query) {
                        $query->whereNull("mobile_no");
                        $query->orWhere("mobile_no", "!=", "");
                    });
                    // $q->where('country_id', $country_id);
                })->first();
            } elseif ($phoneType == 'phoneNumber') {
                $mobile = $this->userHelper->fullPhone($mobile);
                $userData = UserModel::where('is_active', '0')->where(function ($q) use ($mobile, $country_id) {
                    $q->where(function ($query) {
                        $query->whereNull("email");
                        $query->orWhere("email", "!=", "");
                    });
                    $q->where('mobile_no', $mobile);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
                })->first();
            }
            if ($userData && $this->userHelper->checExpireCode($userData)) {
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
                $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent);
                $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);

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
            $requestEmailData = $this->requestData["userEmail"];
            $country_id = $this->requestData["userCountry"];
            $type = $this->userHelper->checkEmailOrPhoneNumber($requestEmailData);
            $userData = false;
            if ($type == 'email') {
                $userData = UserModel::where(function ($q) use ($requestEmailData) {
                    $q->where('email', $requestEmailData);
                })->where('is_active', '1')->first();
            } elseif ($type == 'phoneNumber') {
                $requestEmailData = $this->userHelper->fullPhone($requestEmailData);
                $userData = UserModel::where(function ($q) use ($requestEmailData, $country_id) {
                    $q->where('mobile_no', $requestEmailData);
                    $q->where('country_id', $country_id);
                    $q->where('for_merchant', 0);
                })->where('is_active', '1')->first();
            }

            if (!$userData) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'invalidEmailPhone', 'code' => 406, 'errorData' => []], 200);
            }
            $checkUserCode = $this->userHelper->checkUserLoginCode($userData->id, $this->requestData['userCode']);

            if ($checkUserCode) {
                if ($type == "email") {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivated($userData, $this->userAgent);
                } elseif ($type == "phoneNumber") {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivated($userData, $this->userAgent);
                }
                $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent);
                $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                app('session')->put('tempData', $userData);
                $this->requestData["deviceID"] = empty($this->requestData['deviceID']) ? $this->userHelper->getDeviceID() : $this->requestData["deviceID"];
                $this->userHelper->addUserLogin($this->requestData, $userData->id, true);
                $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'checkSecureActive', $this->userAgent);

                $u = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler');
                return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' =>  Crypt::encrypt(json_encode($u), false), 'code' => 200], 200);
            }
            // $checkUserSession = $this->userHelper->checkUserSession($userData, $this->userAgent, $this->requestData['userCode']);

            // if ($checkUserSession && $this->userHelper->checExpireCode($checkUserSession)) {
            //     $userSession = $this->userHelper->createActiveSession($checkUserSession, $userData, $this->userAgent, $this->requestCart);
            //     if ($type == "email") {
            //         (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSessionActivated($userData, $this->userAgent);
            //     } elseif ($type == "phoneNumber") {
            //         (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendSessionActivated($userData, $this->userAgent);
            //     }
            //     $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            //     app('session')->put('tempData', $userData);
            //     $logHelper->setLog($this->requestData, ['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 'checkSecureActive', $this->userAgent);

            //     return response(['status' => true, 'logged' => true, 'token' => $userSession->hash_token, 'userInfo' => \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($userData, '\OlaHub\UserPortal\ResponseHandlers\HeaderDataResponseHandler'), 'code' => 200], 200);
            // }
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

    // public function subscribe()
    // {
    //     // $this->requestData['email']
    //     $return = ['status' => true, 'msg' => 'successSubscribe'];
    //     return response($return, 200);
    // }
}
