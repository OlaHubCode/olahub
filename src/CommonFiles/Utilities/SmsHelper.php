<?php

namespace OlaHub\UserPortal\Helpers;

class SmsHelper extends OlaHubCommonHelper {

    function sendNewUser($userData, $code) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send new user SMS", "action_startData" => json_encode($userData). $code]);
        $template = 'USR001';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAccountActivationCode($userData, $code) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activation code SMS", "action_startData" => json_encode($userData). $code]);
        $template = 'USR002';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAccountActivated($userData) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activated SMS", "action_startData" => json_encode($userData)]);
        $template = 'USR003';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivation($userData, $fullAgent, $code) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation SMS", "action_startData" => json_encode($userData). $fullAgent. $code]);
        $template = 'USR004';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivationCode($userData, $agent, $code) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation SMS", "action_startData" => json_encode($userData). $agent. $code]);
        $template = 'USR005';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivated($userData, $fullAgent) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activated SMS", "action_startData" => json_encode($userData). $fullAgent]);
        $template = 'USR006';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $replace = ['[UserName]', '[UserSessionAgent]'];
        $with = [$username, $agent];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendForgetPassword($userData) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget password SMS", "action_startData" => json_encode($userData)]);
        $template = 'USR007';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[ResetPasswordLink]', '[UserTempCode]'];
        $with = [$username, FRONT_URL . "/reset_password?token=$userData->reset_pass_token", $userData->reset_pass_code];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendForgetPasswordConfirmation($userData) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget password confirmation SMS", "action_startData" => json_encode($userData)]);
        $template = 'USR008';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $password) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user celebration invition SMS", "action_startData" => json_encode($userData). $celebrationOwner. $celebrationID. $password]);
        $template = 'USR015';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $userData->mobile_no, $password];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendPublishedCelebration($userData, $celebrationName, $celebrationID) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user celebration invition SMS", "action_startData" => json_encode($userData). $celebrationID. "******"]);
        $template = 'USR017';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]', '[CelebrationURL]'];
        $with = [$username, $celebrationName, FRONT_URL . "/celebration/view/" . $celebrationID];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserCODRequest($billing, $userData) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user COD request SMS", "action_startData" => json_encode($billing). json_encode($userData)]);
        $template = 'USR013';
        $userName = "$userData->first_name $userData->last_name";
        $billingNumber = $billing->billing_number;
        $totalAmount = number_format(($billing->billing_total + $billing->billing_fees - $billing->voucher_used), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        $replace = ['[userName]', '[orderNumber]', '[orderAmmount]'];
        $with = [$userName, $billingNumber, $totalAmount];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendDeletedCelebration($userData, $celebrationCreator, $celebrationName, $celebrationOwner) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send deleted celebration SMS", "action_startData" => json_encode($userData). $celebrationCreator. $celebrationName. $celebrationOwner]);
        $template = 'USR016';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationCreatorName]', '[CelebrationEvent]', '[CelebrationOwnerName]'];
        $with = [$username, $celebrationCreator, $celebrationName, $celebrationOwner];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $celebrationName) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send register user celebration invition SMS", "action_startData" => json_encode($userData). $celebrationOwner. $celebrationID. $celebrationName]);
        $template = 'USR014';
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAcceptCelebration($userData, $acceptedName, $celebrationName) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send accept celebration SMS", "action_startData" => json_encode($userData). $acceptedName. $celebrationName]);
        $template = 'USR018';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]'];
        $with = [$acceptedName, $celebrationName];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendCommitedCelebration($userData, $celebrationID, $celebrationName) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send accept celebration SMS", "action_startData" => json_encode($userData). $celebrationID. $celebrationName]);
        $template = 'USR019';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterPublishedCelebrationOwner($userData, $celebrationName, $celebrationID, $password) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send accept celebration SMS", "action_startData" => json_encode($userData). $celebrationName. $celebrationID. "******"]);
        $template = 'USR020';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[UserPassword]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->mobile_no, $password];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendScheduleCelebration($userData, $celebrationName, $celebrationID, $celebrationOwner) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send schedule celebration SMS", "action_startData" => json_encode($userData). $celebrationName. $celebrationID. $celebrationOwner]);
        $template = 'USR021';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[CelebrationOwnerName]'];
        $with = ["$username", FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->mobile_no, $celebrationOwner];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserNewOrderDirect($userData, $billing) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order direct SMS", "action_startData" => json_encode($userData). json_encode($billing)]);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "Paid by: " . $payData["paidBy"];
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "
                    Paid using voucher: " . number_format($payData["orderPayVoucher"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
            $amountCollection .= "
                    Voucher after paid: " . number_format($payData["orderVoucherAfterPay"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "
                    Paid using (" . $payData["orderPayByGate"] . "): </b>" . number_format(($payData["orderPayByGateAmount"]), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $amountCollection];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserNewOrderGift($userData, $billing, $targetData) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order gift SMS", "action_startData" => json_encode($userData). json_encode($billing)]);
        $template = 'USR010';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "Paid by: " . $payData["paidBy"];
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "
                    Paid using voucher: " . number_format($payData["orderPayVoucher"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
            $amountCollection .= "
                    Voucher after paid: " . number_format($payData["orderVoucherAfterPay"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "
                    Paid using (" . $payData["orderPayByGate"] . "): </b>" . number_format(($payData["orderPayByGateAmount"]), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $amountCollection];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNoneRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send none registered target user order gift SMS", "action_startData" => json_encode($userData). json_encode($billing). json_encode($billDetails). json_encode($target)]);
        $template = 'USR011';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $tempPassword = OlaHubCommonHelper::randomString(8, 'str_num');
        $target->password = $tempPassword;
        $target->save();
        $replace = ['[userName]', '[giftsOrder]', '[targetUserName]', '[targetPassword]'];
        $with = [$username, $orderItems, $target->mobile_no, $tempPassword];
        $to = $target->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send registered target user order gift SMS", "action_startData" => json_encode($userData). json_encode($billing). json_encode($billDetails). json_encode($target)]);
        $template = 'USR012';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $replace = ['[userName]', '[giftsOrder]'];
        $with = [$username, $orderItems];
        $to = $target->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    private function handleUserGiftOrderItemsHtml($stores = [], $billing = []) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle user gift order items Html", "action_startData" => json_encode($stores). json_encode($billing)]);
        if (isset($stores['voucher'])) {
            unset($stores['voucher']);
        }
        $return = '<ul>';
        foreach ($stores as $store) {
            $return .= '<li>';
            $return .= '<h3 style="margin-bottom: 0px">From store: (' . $store['storeName'] . ' - ' . $store['storeManagerName'] . ')</h3>';
            $return .= '<ul>';
            foreach ($store['items'] as $item) {
                $return .= '<li>';
                $return .= '<div><b>Item Name: </b>' . $item['itemName'] . '</div>';
                $return .= '<div><b>Item Quantity: </b>' . $item['itemQuantity'] . '</div>';
                $return .= '<div><b>Item Image Link: </b>' . $item['itemImage'] . '</div>';
                if (isset($item['itemAttributes']) && count($item['itemAttributes'])) {
                    $return .= '<ul>';
                    $return .= '<b>Item specs</b><ul>';
                    foreach ($item['itemAttributes'] as $attribute) {
                        $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
                    }
                    $return .= '</ul>';
                }
                $return .= '</li>';
            }
            $return .= '</ul>';
            $return .= '</li>';
        }
        $return .= '</ul><br /> <br />';
        return $return;
    }

    function sendNotRegisterUserGroupInvition($userData, $GroupOwner, $groupID, $password) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user group invition SMS", "action_startData" => json_encode($userData). $GroupOwner. $groupID. "******"]);
        $template = 'USR027';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[GroupURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$GroupOwner, FRONT_URL . "/group/" . $groupID, $userData->mobile_no, $password];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterUserInvition($userData, $invitorName, $password) {
        ////(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user group invition SMS", "action_startData" => json_encode($userData). $invitorName. "******"]);
        $template = 'USR028';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserEmail]', '[UserPassword]', "[OlaHubURL]"];
        $with = [$invitorName, $userData->mobile_no, $password, FRONT_URL . "/login"];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }
    
        function sendUserFailPayment($userData, $billing, $reason) {
        ////(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user group invition SMS", "action_startData" => json_encode($userData). json_encode($billing). $reason]);
        $template = 'USR030';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', "[failReason]"];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $reason];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserCancelConfirmation($userData, $item, $billing) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user cancel confirmation SMS", "action_startData" => json_encode($userData). json_encode($item). json_encode($billing)]);
        $template = 'USR031';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, number_format($item->item_price * $item->quantity, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $item->item_name];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserRefundConfirmation($userData, $item, $billing) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user refund confirmation SMS", "action_startData" => json_encode($userData). json_encode($item). json_encode($billing)]);
        $template = 'USR032';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, number_format($item->item_price * $item->quantity, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $item->item_name];
        $to = $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

}
