<?php

namespace OlaHub\UserPortal\Helpers;

class UserHelper extends OlaHubCommonHelper
{
    //get user data by IP address
    static function getIPInfo()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
       return json_decode(file_get_contents("http://api.ipapi.com/$ip?access_key=52d6f557dd6faf1dbeaa8601450321b6"));
    }

    function fullPhone($phone)
    {
        return $phone = (int) $phone;
    }

    function getDeviceID()
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $macAddr = "";
        $arp = `arp -a $ipAddress`;
        $lines = explode("\n", $arp);
        foreach ($lines as $line) {
            $cols = preg_split('/\s+/', trim($line));
            if ($cols[0] == $ipAddress) {
                $macAddr = $cols[1];
            }
        }
        return empty($macAddr) ? md5($ipAddress) : $macAddr;
    }

    // check user login device
    function checkUserLogin($userId, $deviceId)
    {
        return \OlaHub\UserPortal\Models\UserLoginsModel::where('user_id', $userId)->where('device_id', $deviceId)->first();
    }
    function checkUserLoginCode($userId, $code)
    {
        return \OlaHub\UserPortal\Models\UserLoginsModel::where('user_id', $userId)->where('code', $code)->first();
    }

    function addUserLogin($data, $user_id, $status, $code = null)
    {
        $ipInfo = $this->getIPInfo();
        $row = \OlaHub\UserPortal\Models\UserLoginsModel::where('user_id', $user_id)->where('device_id', $data['deviceID'])->first();
        if (!$row) {
            $userLogin = new \OlaHub\UserPortal\Models\UserLoginsModel;
            $userLogin->device_id = @$data['deviceID'];
            $userLogin->device_model = @$data['deviceModel'];
            $userLogin->device_platform = @$data['platform'];
            $userLogin->user_id = $user_id;
            $userLogin->location = $ipInfo->country_name . ", " . $ipInfo->region_name . ", " . $ipInfo->city;
            $userLogin->ip = $ipInfo->ip;
            $userLogin->geolocation = $ipInfo->latitude . "," .$ipInfo->longitude;
            $userLogin->status = $status;
            $userLogin->code = $code;
            $userLogin->save();
        } else {
            \OlaHub\UserPortal\Models\UserLoginsModel::where('user_id', $user_id)->where('device_id', $data['deviceID'])->update(
                array(
                    'device_id' => @$data['deviceID'],
                    'device_platform' => @$data['platform'],
                    'device_model' => @$data['deviceModel'],
                    'user_id' => $user_id,
                    'location' => $ipInfo->country_name . ", " . $ipInfo->region_name . ", " . $ipInfo->city,
                    'ip' => $ipInfo->ip,
                    'geolocation' => $ipInfo->latitude . "," . $ipInfo->longitude,
                    'status' => $status,
                    'code' => $code
                )
            );
        }
    }

    function checkUnique($value = false, $country_id, $is_phone)
    {
        if ($value && strlen($value) > 3) {
            if ($is_phone) {
                $value = $this->fullPhone($value);
                $exist = \OlaHub\UserPortal\Models\UserModel::where('country_id', $country_id)
                    ->where("mobile_no", $value)
                    ->where("for_merchant", 0)->first();
            } else {
                $exist = \OlaHub\UserPortal\Models\UserModel::where('email', $value)
                    ->orWhere('mobile_no', $value)
                    ->orWhere('facebook_id', $value)
                    ->orWhere('google_id', $value)
                    ->orWhere('twitter_id', $value)
                    ->first();
            }
            if (!$exist) {
                return true;
            }
        } elseif (strlen($value) <= 3) {
            return TRUE;
        }
        return false;
    }

    function createProfileSlug($userName, $userId)
    {
        $lower = strtolower($userName);
        $replace = str_replace(' ', '_', $lower);
        $replaceSpcial = preg_replace('/^[\p{Arabic}a-zA-Z\p{N}]+\h?[\p{N}\p{Arabic}a-zA-Z]*$/u', '', $replace);
        $lowerSpecial = strtolower(trim($replaceSpcial, '-'));
        $replaceDashes = preg_replace("/[\/_|+ -]+/", '.', $lowerSpecial);
        $profileSlug = $replaceDashes . '.' . $userId;

        return $profileSlug;
    }

    function createActiveSession($userSession, $userData, $userAgent, $requestCart)
    {
        if (!$userSession) {
            $userSession = new \OlaHub\UserPortal\Models\UserSessionModel;
        }
        $code = parent::randomString(6, 'num');
        $userSession->hash_token = (new \OlaHub\UserPortal\Helpers\SecureHelper)->setTokenHashing($userAgent, $userData->id, $code);
        $userSession->activation_code = $code;
        $userSession->user_id = $userData->id;
        $userSession->user_agent = $userAgent;
        $userSession->status = '1';
        $userSession->save();
        (new \OlaHub\UserPortal\Helpers\CartHelper)->setSessionCartData($userData->id, $requestCart);
        return $userSession;
    }

    function createNotActiveSession($userSession, $userData, $userAgent, $requestCart)
    {
        if (!$userSession) {
            $userSession = new \OlaHub\UserPortal\Models\UserSessionModel;
        }
        $code = parent::randomString(6, 'num');
        $userSession->hash_token = (new \OlaHub\UserPortal\Helpers\SecureHelper)->setTokenHashing($userAgent, $userData->id, $code);
        $userSession->activation_code = $code;
        $userSession->user_id = $userData->id;
        $userSession->user_agent = $userAgent;
        $userSession->status = '0';
        $userSession->save();
        (new \OlaHub\UserPortal\Helpers\CartHelper)->setSessionCartData($userData->id, $requestCart);
        return $userSession;
    }

    function checkUserSession($userData, $userAgent, $activationCode = false)
    {
        if ($activationCode) {
            $session = \OlaHub\UserPortal\Models\UserSessionModel::where('user_id', $userData->id)->where('user_agent', $userAgent)->where('activation_code', $activationCode)->where('status', 0)->first();
        } else {
            $session = \OlaHub\UserPortal\Models\UserSessionModel::where('user_id', $userData->id)->where('user_agent', $userAgent)->first();
        }
        return $session;
    }

    function checExpireCode($userData, $column = 'updated_at')
    {
        $return = false;
        if (isset($userData->$column) && (strtotime($userData->$column . "+30 minutes") >= time())) {
            $return = TRUE;
        }
        return $return;
    }

    function checkEmailPhoneChange($userData, $requestData)
    {
        if (isset($requestData['userEmail']) && $userData->email != $requestData['userEmail'] && isset($requestData["oldPassword"]) && (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($requestData["oldPassword"], $userData->password)) {
            return ['change' => 'email'];
        }
        if (isset($requestData['userPhoneNumber']) && $userData->mobile_no != $requestData['userPhoneNumber'] && isset($requestData["oldPassword"]) && (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($requestData["oldPassword"], $userData->password)) {
            return ['change' => 'phone'];
        }
        return TRUE;
    }

    function sendUpdateActivationCode($userData, $checkChanges)
    {
        if (is_array($checkChanges) && array_key_exists('change', $checkChanges)) {
            $userData->activation_code = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6, 'num');
            $userData->is_active = '0';
            $userData->save();
            if ($checkChanges['change'] == 'email') {
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return ['status' => TRUE, 'verified' => '1', 'msg' => 'apiActivationCodeEmail', 'code' => 200];
            } else {
                (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendAccountActivationCode($userData, $userData->activation_code);
                return ['status' => TRUE, 'verified' => '1', 'msg' => 'apiActivationCodePhone', 'code' => 200];
            }
        } elseif ($checkChanges) {
            return false;
        }
        return false;
    }

    function sendUserEmail($email, $code, $template = 'user_activation_code')
    {
        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendMail) {
            $sendMail->template_code = $template;
            $sendMail->replace = ['[FranActivationCode]'];
            $sendMail->replace_with = [$code];
            $sendMail->to = $email;
            $sendMail->send();
        }
    }

    function sendForgetEmail($email, $code, $template = 'user_forgetPass_temaplate')
    {
        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendMail) {
            $sendMail->template_code = $template;
            $sendMail->replace = ['[FranTempPass]'];
            $sendMail->replace_with = [$code];
            $sendMail->to = $email;
            $sendMail->send();
        }
    }

    function uploadUserImage($user, $columnName, $userPhoto = false)
    {
        if ($userPhoto) {
            $mimes = ['image/bmp', 'image/gif', 'image/jpeg', 'image/x-citrix-jpeg', 'image/png', 'image/x-citrix-png', 'image/x-png'];
            $mime = $userPhoto->getMimeType();
            if (!in_array($mime, $mimes)) {
                $log->setLogSessionData(['response' => ['status' => false, 'path' => false, 'msg' => 'Unsupported file type']]);
                $log->saveLogSessionData();
                return response(['status' => false, 'path' => false, 'msg' => 'Unsupported file type']);
            }
            $extension = $userPhoto->getClientOriginalExtension();
            $fileNameStore = uniqid() . '.' . $extension;
            $filePath = DEFAULT_IMAGES_PATH . 'users/' . app('session')->get('tempID');
            if (!file_exists($filePath)) {
                mkdir($filePath, 0777, true);
            }
            $path = $userPhoto->move($filePath, $fileNameStore);

            if ($user->$columnName) {
                $oldImage = $user->$columnName;
                @unlink(DEFAULT_IMAGES_PATH . '/' . $oldImage);
            }
            return "users/" . app('session')->get('tempID') . "/$fileNameStore";
        }
        return $userPhoto;
    }

    function uploadUserImageAsPost( $path = false)
    {
        if ($path) {
            $new_path = DEFAULT_IMAGES_PATH . 'posts/' . app('session')->get('tempID');
            $path = DEFAULT_IMAGES_PATH . $path;
            $fileName = explode('/', $path);

            if (!file_exists($new_path)) {
                mkdir($new_path, 0777, true);
            }

            @copy($path , $new_path .'/'.  end($fileName));
            return "posts/" . app('session')->get('tempID') . "/". end($fileName);

        }
        return $path;
    }


    function checkEmailOrPhoneNumber($requestData)
    {
        if (preg_match("/^[^@]+@[^@]+\.[a-z]{2,6}$/i", $requestData)) {
            return "email";
        } elseif (preg_match("/^[0-9]+$/i", $requestData)) {
            return "phoneNumber";
        }
        return FALSE;
    }

    function handleUserPhoneNumber($phoneNumber = false)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle user phone number", "action_startData" => $phoneNumber]);
        $return = NULL;
        if ($phoneNumber) {
            if (substr($phoneNumber, 0, 2) == "00") {
                $return = substr_replace($phoneNumber, "+", 0, 2);
            } else {
                $return = $phoneNumber;
            }
        }
        return $return;
    }
    
    public static function buildAppleData($data)
    {
        $output = array();
        $user = null;
        if(isset($data['user']))
            $user = json_decode($data['user']);
        $output['apple_token'] = $data['id_token'];
        $output['first_name'] = !empty($user) ? $user->name->firstName : "";
        $output['last_name'] = !empty($user) ? $user->name->lastName : "";
        $output['email'] = $data['email'] ? $data['email'] : "";
        return $output;
    }

}
