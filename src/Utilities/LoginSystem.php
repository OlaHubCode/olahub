<?php

namespace OlaHub\UserPortal\Libraries;

class LoginSystem {

    public
            $userAgent,
            $userToken,
            $userData,
            $userSessionModel,
            $userModel,
            $portalUtilities,
            $passwordMatchFunc,
            $tokenMatchFunc,
            $passwordHashMakerFunc,
            $tokenHashMakerFunc,
            $requestPassword,
            $requestNewPassword,
            $requestCode,
            $userIDColumn,
            $countryID,
            $userAgentColumn;

    public function __construct() {
        
    }

    function checkUser($data,$requestPassword,$sessionAgent) {
        if (\OlaHub\MerchantPortal\Helpers\SecureHelper::matchPasswordHash($requestPassword, $data->password)) {
            if (!isset($data->is_active) || $data->is_active) {
                return $this->checkAgent($sessionAgent);
            } else {
                return ['status' => false, 'msg' => 'No data found, Please contact your Admin to check the account'];
            }
        } else {
            return ['status' => false, 'msg' => 'Password not correct'];
        }
    }

    function firstLogin() {
        $session = new $this->userSessionModel;
        $sessionData = $session->where('hash_token', $this->userToken)
                ->where('status', '1')
                ->where($this->userAgentColumn, $this->userAgent)
                ->first();
        if ($sessionData) {
            $id = $sessionData->{$this->userIDColumn};
            if ($this->portalUtilities->{$this->tokenMatchFunc}($this->userToken, $this->userAgent, $id, $sessionData->activation_code)) {
                $userModel = new $this->userModel;
                $this->userData = $userModel->where('is_first_login', '1')->find($id);
                if ($this->userData) {
                    return $this->changeUserPassword();
                }
            }
        }
        return ['status' => false, 'logged' => false, 'token' => false];
    }

    function activateUser() {
        $session = new $this->userSessionModel;
        $data = $session->where($this->userIDColumn, $this->userData->id)
                ->where($this->userAgentColumn, $this->userAgent)
                ->where('activation_code', $this->requestCode)
                ->where('status', '0')
                ->first();
        if ($data && $this->checExpireCode($data)) {
            $data->activation_code = null;
            $data->status = '1';
            $data->save();
            return $this->checkAgent();
        }
        return ['status' => false, 'msg' => 'Wrong data sent'];
    }

    function forgetPasswordUser() {
        $password = \OlaHub\Helpers\OlaHubCommonHelper::randomString(6);
        $this->userData->password = $password;
        $this->userData->is_first_login = '1';
        $this->userData->old_password = null;
        $this->userData->save();
        $session = new $this->userSessionModel;
        $session->where($this->userIDColumn, $this->userData->id)->delete();
        \OlaHub\Helpers\OlaHubCommonHelper::setDefLang($this->countryID);
            $template = \OlaHub\UserPortal\Models\MessageTemplate::where('code', 'franchise_forgetPass_temaplate')->first();
            if ($template) {
                $subject = \OlaHub\Helpers\OlaHubCommonHelper::returnCurrentLangField($template, "subject");
                $body = str_replace(['[FranTempPass]'], [$password], \OlaHub\Helpers\OlaHubCommonHelper::returnCurrentLangField($template, "body"));
                $email = new \OlaHub\UserPortal\Libraries\SendEmails;
                $email->subject = $subject;
                $email->body = $body;
                $email->to = $this->userData->email;
                $email->send();
            }
        return ['status' => true, 'msg' => 'Kindly check you E-Mail for new password'];
    }

    function logoutUser() {
        $session = new $this->userSessionModel;
        $data = $session->where($this->userAgentColumn, $this->userAgent)
                ->where('hash_token', $this->userToken)
                ->where('status', '1')
                ->first();
        if ($data) {
            $data->activation_code = null;
            $data->hash_token = null;
            $data->save();
            return ['status' => true, 'logged' => false, 'token' => false];
        }
        return ['status' => false, 'msg' => 'Wrong data sent'];
    }

    public function checkAgent($sessionAgent) {
        $session = new $this->userSessionModel;
        $data = $session->where($this->userIDColumn, $this->userData->id)->where($this->userAgentColumn, $this->userAgent)->first();
        if ($data) {
            if ($data->status) {
                return $this->createNewSession($data);
            }
            return $this->resendActivationCode($data);
        }
        return $this->createNewAgent();
    }

    private function changeUserPassword() {
        if ($this->portalUtilities->{$this->passwordMatchFunc}($this->requestPassword, $this->userData->password)) {
            $this->userData->password = $this->requestNewPassword;
            $this->userData->is_first_login = '0';
            $this->userData->save();
            $session = new $this->userSessionModel;
            $sessionData = $session->where('hash_token', $this->userToken)
                    ->where('status', '1')
                    ->where($this->userAgentColumn, $this->userAgent)
                    ->first();
            $sessionData->hash_token = null;
            $sessionData->save();
            return ['status' => true, 'logged' => 'confirmed', 'token' => false];
        } else {
            return ['status' => false, 'msg' => 'Password not correct'];
        }
        //
    }

    private function checExpireCode($data) {
        $return = false;
        if (isset($data->updated_at) && (strtotime($data->updated_at . "+30 minutes") >= time())) {
            $return = TRUE;
        }
        return $return;
    }

    private function sendActivationEmail($email, $code) {
        $template = \OlaHub\UserPortal\Models\MessageTemplate::where('code', 'session_activation_code')->first();
        if ($template) {
            $subject = \OlaHub\Helpers\OlaHubCommonHelper::returnCurrentLangField($template, "subject");
            $body = str_replace(['[FranActivationCode]'], [$code], \OlaHub\Helpers\OlaHubCommonHelper::returnCurrentLangField($template, "body"));
            $sendEmail = new \OlaHub\UserPortal\Libraries\SendEmails;
            $sendEmail->subject = $subject;
            $sendEmail->body = $body;
            $sendEmail->to = $email;
            $sendEmail->send();
        }
    }

    private function createNewAgent() {
        $code = \OlaHub\Helpers\OlaHubCommonHelper::randomString(6, 'num');
        $session = new $this->userSessionModel;
        $session->{$this->userIDColumn} = $this->userData->id;
        $session->{$this->userAgentColumn} = $this->userAgent;
        $session->activation_code = $code;
        $session->save();
        $this->sendActivationEmail($this->userData->email, $code);
        return ['status' => true, 'logged' => 'new', 'token' => false];
    }

    private function resendActivationCode($data) {
        $code = \OlaHub\Helpers\OlaHubCommonHelper::randomString(6, 'num');
        $data->activation_code = $code;
        $data->save();
        $this->sendActivationEmail($data->email, $code);
        return ['status' => true, 'logged' => 'new', 'token' => false];
    }

    private function createNewSession($data) {
        $code = \OlaHub\Helpers\OlaHubCommonHelper::randomString(6, 'num');
        $id = $this->userData->id;
        $token = $this->portalUtilities->{$this->tokenHashMakerFunc}($this->userAgent, $id, $code);
        $token = $this->portalUtilities->setPasswordHashing(serialize([
            'agent' => $this->userAgent,
            'id' => $id,
            'code' => $code,
        ]));
        $data->hash_token = $token;
        $data->activation_code = $code;
        $data->save();
        return ['status' => true, 'logged' => true, 'token' => $token];
    }

}
