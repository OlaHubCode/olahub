<?php

namespace OlaHub\UserPortal\Helpers;

class UserHelper extends OlaHubCommonHelper {

    function checkUnique($value = false) {
        if ($value && strlen($value) > 3) {
            $exist = \OlaHub\UserPortal\Models\UserModel::where('email', $value)
                    ->orWhere('mobile_no', $value)
                    ->orWhere('facebook_id', $value)
                    ->orWhere('google_id', $value)
                    ->orWhere('twitter_id', $value)
                    ->first();
            if (!$exist) {
                return true;
            }
        } elseif (strlen($value) <= 3) {
            return TRUE;
        }
        return false;
    }

    function createProfileSlug($userName,$userId) {
        /*$profileSlug = parent::createSlugFromString($userName, '.');
        $existSlug = \Illuminate\Support\Facades\DB::table('users')
                        ->where('profile_url', 'LIKE', $profileSlug . '%')->orderBy('profile_url', 'desc')->first();
        if ($existSlug) {
            $values = explode(".", $existSlug->profile_url);
            if (is_array($values) && end($values)) {
                $profileSlug = $profileSlug . '.' . ((int) end($values) + 1);
            } else {
                $profileSlug = $profileSlug . '.' . 1;
            }
        }*/
        
        
        $lower = strtolower($userName);
        $replace = str_replace(' ', '_', $lower);
        $replaceSpcial = preg_replace('/^[\p{Arabic}a-zA-Z\p{N}]+\h?[\p{N}\p{Arabic}a-zA-Z]*$/u', '', $replace);
        $lowerSpecial = strtolower(trim($replaceSpcial, '-'));
        $replaceDashes = preg_replace("/[\/_|+ -]+/", '.', $lowerSpecial);
        $profileSlug = $replaceDashes . '.' . $userId;
        
        return $profileSlug;
    }

    function createActiveSession($userSession, $userData, $userAgent, $requestCart) {
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

    function createNotActiveSession($userSession, $userData, $userAgent, $requestCart) {
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

    function checkUserSession($userData, $userAgent, $activationCode = false) {
        if ($activationCode) {
            $session = \OlaHub\UserPortal\Models\UserSessionModel::where('user_id', $userData->id)->where('user_agent', $userAgent)->where('activation_code', $activationCode)->where('status', 0)->first();
        } else {
            $session = \OlaHub\UserPortal\Models\UserSessionModel::where('user_id', $userData->id)->where('user_agent', $userAgent)->first();
        }
        return $session;
    }

    function checExpireCode($userData, $column = 'updated_at') {
        $return = false;
        if (isset($userData->$column) && (strtotime($userData->$column . "+30 minutes") >= time())) {
            $return = TRUE;
        }
        return $return;
    }

    function checkEmailPhoneChange($userData, $requestData) {
        if (isset($requestData['userEmail']) && $userData->email != $requestData['userEmail'] && isset($requestData["oldPassword"]) && (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($requestData["oldPassword"], $userData->password)) {
            return ['change' => 'email'];
        }
        if (isset($requestData['userPhoneNumber']) && $userData->mobile_no != $requestData['userPhoneNumber'] && isset($requestData["oldPassword"]) && (new \OlaHub\UserPortal\Helpers\SecureHelper)->matchPasswordHash($requestData["oldPassword"], $userData->password)) {
            return ['change' => 'phone'];
        }
        return TRUE;
    }

    function sendUpdateActivationCode($userData, $checkChanges) {
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

    function sendUserEmail($email, $code, $template = 'user_activation_code') {
        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendMail) {
            $sendMail->template_code = $template;
            $sendMail->replace = ['[FranActivationCode]'];
            $sendMail->replace_with = [$code];
            $sendMail->to = $email;
            $sendMail->send();
        }
    }

    function sendForgetEmail($email, $code, $template = 'user_forgetPass_temaplate') {
        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendMail) {
            $sendMail->template_code = $template;
            $sendMail->replace = ['[FranTempPass]'];
            $sendMail->replace_with = [$code];
            $sendMail->to = $email;
            $sendMail->send();
        }
    }

    function uploadUserImage($user, $columnName, $userPhoto = false) {
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
                @unlink(DEFAULT_IMAGES_PATH.'/' . $oldImage);
            }
            return "users/" . app('session')->get('tempID') . "/$fileNameStore";
        }
        return $userPhoto;
    }

    function checkEmailOrPhoneNumber($requestData) {
        if (preg_match("/^[^@]+@[^@]+\.[a-z]{2,6}$/i", $requestData)) {
            return "email";
        } elseif (preg_match("/^[0-9]+$/i", $requestData)) {
            return "phoneNumber";
        }
        return FALSE;
    }

    function handleUserPhoneNumber($phoneNumber = false) {
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

}
