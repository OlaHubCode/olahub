<?php

namespace OlaHub\UserPortal\Helpers;

class EmailHelper extends OlaHubCommonHelper {

    function sendNewUser($userData, $code) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send new user Email","action_startData" => json_encode($userData) . $code]);
        $template = 'USR001';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAccountActivationCode($userData, $code) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activation code Email","action_startData" => json_encode($userData) . $code]);
        $template = 'USR002';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAccountActivated($userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activated Email","action_startData" => json_encode($userData)]);
        $template = 'USR003';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivation($userData, $fullAgent, $code) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation Email","action_startData" => json_encode($userData) .  $fullAgent . $code]);
        $template = 'USR004';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $userData;
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivationCode($userData, $agent, $code) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation code Email","action_startData" => json_encode($userData) .  $agent . $code]);
        $template = 'USR005';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivated($userData, $fullAgent) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activated Email","action_startData" => json_encode($userData) .  $fullAgent]);
        $template = 'USR006';
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionAgent]'];
        $with = [$username, $agent];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendForgetPassword($userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget password Email","action_startData" => json_encode($userData)]);
        $template = 'USR007';
        $username = "$userData->first_name $userData->last_name";
        $link = FRONT_URL . "/reset_password?token=$userData->reset_pass_token";
        $replace = ['[UserName]', '[ResetPasswordLink]', '[UserTempCode]'];
        $with = [$username, "<a href='$link'>$link</a>", $userData->reset_pass_code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendForgetPasswordConfirmation($userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget passworf confirmation Email","action_startData" => json_encode($userData)]);
        $template = 'USR008';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesCODRequest($billingDetails, $billing, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales COD request Email","action_startData" => json_encode($billingDetails) . json_encode($billing) . json_encode($userData)]);
        $template = 'SLS003';
        $customerName = "$userData->first_name $userData->last_name";
        $customerPhone = $userData->mobile_no;
        $customerEmail = $userData->email;
        $billingNumber = $billing->billing_number;
        $orderItems = $this->handleSalesOrderItemsHtml($billingDetails, $billing);
        $billingAddress = unserialize($billing->order_address);
//        $reciverName = $billingAddress['full_name'];
//        $reciverPhone = $billingAddress['phone'];
//        $reciverEmail = $billingAddress['email'];
        $reciverAddress = $billingAddress['city'] . " - " . $billingAddress['state'] . " - " . $billingAddress['address'] . " - " . $billingAddress['zipcode'];
        $totalAmount = $billing->billing_total + $billing->billing_fees . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        $voucherAmount = number_format($billing->voucher_used, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        $cashAmount = number_format(($billing->billing_total + $billing->billing_fees - $billing->voucher_used), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        $replace = ['[customerName]', '[customerPhone]', '[customerEmail]', '[billingNumber]', '[orderItems]', '[totalAmount]', '[voucherAmount]', '[cashAmount]', "[customerAddress]"];
        $with = [$customerName, $customerPhone, $customerEmail, $billingNumber, $orderItems, $totalAmount, $voucherAmount, $cashAmount, $reciverAddress];
        $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesCancelItem($purchasedItem, $billing, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales cancel item Email","action_startData" => json_encode($purchasedItem) . json_encode($billing) . json_encode($userData)]);
        $template = 'SLS005';
        $customerName = "$userData->first_name $userData->last_name";
        $customerPhone = $userData->mobile_no;
        $customerEmail = $userData->email;
        $billingNumber = $billing->billing_number;
        $orderItems = $this->handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing);
        $replace = ['[customerName]', '[customerPhone]', '[customerEmail]', '[billingNumber]', '[orderItems]'];
        $with = [$customerName, $customerPhone, $customerEmail, $billingNumber, $orderItems];
        $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesRefundItem($purchasedItem, $billing, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales refund item Email","action_startData" => json_encode($purchasedItem) . $billing . json_encode($userData)]);
        $template = 'SLS006';
        $customerName = "$userData->first_name $userData->last_name";
        $customerPhone = $userData->mobile_no;
        $customerEmail = $userData->email;
        $billingNumber = $billing->billing_number;
        $orderItems = $this->handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing);
        $replace = ['[customerName]', '[customerPhone]', '[customerEmail]', '[billingNumber]', '[orderItems]'];
        $with = [$customerName, $customerPhone, $customerEmail, $billingNumber, $orderItems];
        $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserCODRequest($billing, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Senduser COD request Email","action_startData" => json_encode($billing) . json_encode($userData)]);
        $template = 'USR013';
        $userName = "$userData->first_name $userData->last_name";
        $billingNumber = $billing->billing_number;
        $totalAmount = number_format(($billing->billing_total + $billing->billing_fees - $billing->voucher_used), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency);
        $replace = ['[userName]', '[orderNumber]', '[orderAmmount]'];
        $with = [$userName, $billingNumber, $totalAmount];
        $to = [[$userData->email, $userName]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * Direct purchased EMails
     * 
     */

    function sendSalesNewOrderDirect($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order direct Email" , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'SLS001';
        $billingAddress = unserialize($bill->order_address);
        $userName = "$userData->first_name $userData->last_name";
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['city'] . " - " . $billingAddress['state'] . " - " . $billingAddress['address'] . " - " . $billingAddress['zipcode'];
        $orderItems = $this->handleSalesOrderItemsHtml($billDetails, $bill);
        $replace = ['[userName]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderItems]'];
        $with = [$userName, $customerName, $customerPhone, $customerAddress, $orderItems];
        $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendMerchantNewOrderDirect($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order direct Email"  , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER012';
        $billingAddress = unserialize($bill->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['city'] . " - " . $billingAddress['state'] . " - " . $billingAddress['address'] . " - " . $billingAddress['zipcode'];
        foreach ($billDetails as $store) {
            $merchantName = $store['storeManagerName'];
            $orderItems = $this->handleMerchantOrderItemsHtml($store['items'], $bill);
            $replace = ['[merchantName]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderItems]'];
            $with = [$merchantName, $customerName, $customerPhone, $customerAddress, $orderItems];
            if (PRODUCTION_LEVEL) {
                $to = [[$store['storeEmail'], $store['storeManagerName']]];
            } else {
                $to = [["mohamed.elabsy@olahub.com", $store['storeManagerName']]];
            }
            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendUserNewOrderDirect($userData, $billing) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order direct Email" , "action_startData" =>  json_encode($userData) . json_encode($billing)]);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "<div><b>Paid by: </b>" . $payData["paidBy"] . "</div>";
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "<div><b>Paid using voucher: </b>" . number_format($payData["orderPayVoucher"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
            $amountCollection .= "<div><b>Voucher after paid: </b>" . number_format($payData["orderVoucherAfterPay"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "<div><b>Paid using (" . $payData["orderPayByGate"] . "): </b>" . number_format(($payData["orderPayByGateAmount"]), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $amountCollection];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserFailPayment($userData, $billing, $reason) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user fail payment Email" , "action_startData" =>  json_encode($userData) . json_encode($billing) . $reason]);
        $template = 'USR030';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', "[failReason]"];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $reason];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserCancelConfirmation($userData, $item, $billing) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user cancel confirmation Email" , "action_startData" =>  json_encode($userData) . json_encode($item) . json_encode($billing)]);
        $template = 'USR031';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, number_format($item->item_price * $item->quantity, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $item->item_name];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserRefundConfirmation($userData, $item, $billing) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user refund confirmation Email" , "action_startData" =>  json_encode($userData) . json_encode($item) . json_encode($billing)]);
        $template = 'USR032';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, number_format($item->item_price * $item->quantity, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $item->item_name];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * End direct purchased
     * 
     */
    /*
     * 
     * Gift purchased EMails
     * 
     */

    function sendSalesNewOrderGift($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order gift Email" , "action_startData" =>  json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        $template = 'SLS002';
        $billingAddress = unserialize($bill->order_address);
        $userName = "$userData->first_name $userData->last_name";
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['city'] . " - " . $billingAddress['state'] . " - " . $billingAddress['address'] . " - " . $billingAddress['zipcode'];
        $orderItems = $this->handleSalesOrderItemsHtml($billDetails, $bill);
        $replace = ['[userName]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderItems]', '[cardMessage]', '[giftDate]'];
        $with = [$userName, $customerName, $customerPhone, $customerAddress, $orderItems, $bill->gift_message, OlaHubCommonHelper::convertStringToDate($bill->gift_date)];
        $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendMerchantNewOrderGift($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order gift Email" , "action_startData" =>  json_encode($billDetails) . json_encode($bill). json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER013';
        $billingAddress = unserialize($bill->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['city'] . " - " . $billingAddress['state'] . " - " . $billingAddress['address'] . " - " . $billingAddress['zipcode'];
        foreach ($billDetails as $store) {
            $merchantName = $store['storeManagerName'];
            $orderItems = $this->handleMerchantOrderItemsHtml($store['items'], $bill);
            $replace = ['[merchantName]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderItems]', '[cardMessage]', '[giftDate]'];
            $with = [$merchantName, $customerName, $customerPhone, $customerAddress, $orderItems, $bill->gift_message, OlaHubCommonHelper::convertStringToDate($bill->gift_date)];
            if (PRODUCTION_LEVEL) {
                $to = [[$store['storeEmail'], $store['storeManagerName']]];
            } else {
                $to = [["mohamed.elabsy@olahub.com", $store['storeManagerName']]];
            }

            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendUserNewOrderGift($userData, $billing, $targetData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order gift Email" , "action_startData" => json_encode($userData) . json_encode($billing)]);
        $template = 'USR010';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "<div><b>Paid by: </b>" . $payData["paidBy"] . "</div>";
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "<div><b>Paid using voucher: </b>" . number_format($payData["orderPayVoucher"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
            $amountCollection .= "<div><b>Voucher after paid: </b>" . number_format($payData["orderVoucherAfterPay"], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "<div><b>Paid using (" . $payData["orderPayByGate"] . "): </b>" . number_format(($payData["orderPayByGateAmount"]), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $amountCollection];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNoneRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send none registered target user order gift Email" , "action_startData" => json_encode($userData) . json_encode($billing) .  json_encode($billDetails) . json_encode($target)]);
        $template = 'USR011';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $tempPassword = OlaHubCommonHelper::randomString(8, 'str_num');
        $target->password = $tempPassword;
        $target->save();
        $replace = ['[userName]', '[giftsOrder]', '[targetUserName]', '[targetPassword]'];
        $with = [$username, $orderItems, $target->email, $tempPassword];
        $to = [[$target->email, $targetName]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send registered target user order gift Email" , "action_startData" => json_encode($userData) . json_encode($billing) .  json_encode($billDetails) . json_encode($target)]);
        $template = 'USR012';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $replace = ['[userName]', '[giftsOrder]'];
        $with = [$username, $orderItems];
        $to = [[$target->email, $targetName]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * End gift purchased
     * 
     */

    /*
     * 
     * Start celebration purchased
     * 
     */

    function sendUserPaymentCelebration($userData, $billing) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user payment celebration Email" , "action_startData" => json_encode($userData) . json_encode($billing)]);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        $amountCollection = "<div><b>Paid by: </b>$billing->paid_by</div>";
        if ($billing->voucher_used > 0) {
            $amountCollection .= "<div><b>Paid using voucher: </b>" . number_format($billing->voucher_used, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
            $amountCollection .= "<div><b>Voucher after paid: </b>" . number_format($billing->voucher_after_pay, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }

        if ($billing->voucher_used > 0 && $billing->billing_total > $billing->voucher_used) {
            $amountCollection .= "<div><b>Paid using ($billing->paid_by): </b>" . number_format(($billing->billing_total - $billing->voucher_used), 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . "</div>";
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency), $amountCollection];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * End celebration purchased
     * 
     */

    function sendSalesNewOrderCelebration($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order celebration Email" , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        $template = 'SLS002';
        $celebrationId = $bill->pay_for;
        $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where("id", $celebrationId)->first();
        if ($celebration) {
            $celebrationAddress = $celebration->shippingAddress;
            $target = $celebration->ownerUser()->withOutGlobalScope('notTemp')->first();
            $customerName = "$target->first_name $target->last_name";
            $customerPhone = $celebrationAddress->shipping_address_phone_no;
            $customerEmail = $target->email;
            $customerAddress = $celebrationAddress->shipping_address_city . " - " . $celebrationAddress->shipping_address_state . " - " . $celebrationAddress->shipping_address_address_line1 . ", " . $celebrationAddress->shipping_address_address_line2 . " - " . $celebrationAddress->shipping_address_zip_code;
            $userName = "$userData->first_name $userData->last_name";
            $orderItems = $this->handleSalesOrderItemsHtml($billDetails, $bill);
            $replace = ['[userName]', '[customerName]', '[customerPhone]', '[customerEmail]', '[customerAddress]', '[orderItems]', '[cardMessage]'];
            $with = [$userName, $customerName, $customerPhone, $customerEmail, $customerAddress, $orderItems, $bill->gift_message];
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendSalesScheduledOrderCelebration($billDetails, $bill, $celebration, $target) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales scheduled order celebration Email" , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($celebration). json_encode($target)]);
        $template = 'SLS004';
        if ($celebration) {
            $celebrationAddress = $celebration->shippingAddress;
            $customerName = "$target->first_name $target->last_name";
            $customerPhone = $celebrationAddress->shipping_address_phone_no;
            $customerEmail = $target->email;
            $customerAddress = $celebrationAddress->shipping_address_city . " - " . $celebrationAddress->shipping_address_state . " - " . $celebrationAddress->shipping_address_address_line1 . ", " . $celebrationAddress->shipping_address_address_line2 . " - " . $celebrationAddress->shipping_address_zip_code;
            $orderItems = $this->handleSalesOrderItemsHtml($billDetails, $bill);
            $replace = ['[customerName]', '[customerPhone]', '[customerEmail]', '[customerAddress]', '[orderItems]', '[cardMessage]', "[celebrationPublishDate]"];
            $with = [$customerName, $customerPhone, $customerEmail, $customerAddress, $orderItems, $bill->gift_message, OlaHubCommonHelper::convertStringToDate($celebration->celebration_date)];
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendMerchantNewOrderCelebration($billDetails, $bill, $userData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order clebration Email" , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER014';
        $celebrationId = $bill->pay_for;
        $celebration = \OlaHub\UserPortal\Models\CelebrationModel::where("id", $celebrationId)->first();
        if ($celebration) {
            $celebrationAddress = $celebration->shippingAddress;
            $target = $celebration->ownerUser()->withOutGlobalScope('notTemp')->first();
            $customerName = "$target->first_name $target->last_name";
            $customerPhone = $celebrationAddress->shipping_address_phone_no;
            $customerEmail = $target->email;
            $customerAddress = $celebrationAddress->shipping_address_city . " - " . $celebrationAddress->shipping_address_state . " - " . $celebrationAddress->shipping_address_address_line1 . ", " . $celebrationAddress->shipping_address_address_line2 . " - " . $celebrationAddress->shipping_address_zip_code;
            foreach ($billDetails as $store) {
                $merchantName = $store['storeManagerName'];
                $orderItems = $this->handleMerchantOrderItemsHtml($store['items'], $bill);
                $replace = ['[merchantName]', '[customerName]', '[customerPhone]', '[customerEmail]', '[customerAddress]', '[orderItems]', '[cardMessage]'];
                $with = [$merchantName, $customerName, $customerPhone, $customerEmail, $customerAddress, $orderItems, $bill->gift_message];
                if (PRODUCTION_LEVEL) {
                    $to = [[$store['storeEmail'], $store['storeManagerName']]];
                } else {
                    $to = [["mohamed.elabsy@olahub.com", $store['storeManagerName']]];
                }

                parent::sendEmail($to, $replace, $with, $template);
            }
        }
    }

    function sendMerchantScheduledOrderCelebration($billDetails, $bill, $celebration, $target) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant scheduled order clebration Email" , "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($celebration) . json_encode($target)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER018';
        if ($celebration) {
            $celebrationAddress = $celebration->shippingAddress;
            $customerName = "$target->first_name $target->last_name";
            $customerPhone = $celebrationAddress->shipping_address_phone_no;
            $customerEmail = $target->email;
            $customerAddress = $celebrationAddress->shipping_address_city . " - " . $celebrationAddress->shipping_address_state . " - " . $celebrationAddress->shipping_address_address_line1 . ", " . $celebrationAddress->shipping_address_address_line2 . " - " . $celebrationAddress->shipping_address_zip_code;
            foreach ($billDetails as $store) {
                $merchantName = $store['storeManagerName'];
                $orderItems = $this->handleMerchantOrderItemsHtml($store['items'], $bill);
                $replace = ['[merchantName]', '[customerName]', '[customerPhone]', '[customerEmail]', '[customerAddress]', '[orderItems]', '[cardMessage]', "[celebrationPublishDate]"];
                $with = [$merchantName, $customerName, $customerPhone, $customerEmail, $customerAddress, $orderItems, $bill->gift_message, OlaHubCommonHelper::convertStringToDate($celebration->celebration_date)];
                if (PRODUCTION_LEVEL) {
                    $to = [[$store['storeEmail'], $store['storeManagerName']]];
                } else {
                    $to = [["mohamed.elabsy@olahub.com", $store['storeManagerName']]];
                }

                parent::sendEmail($to, $replace, $with, $template);
            }
        }
    }

    private function handleUserGiftOrderItemsHtml($stores = [], $billing = []) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle user gift order items Html" , "action_startData" => json_encode($stores) . json_encode($billing)]);
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
                if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                    $return .= '<ul>';
                    $return .= '<b>Item specs</b><ul>';
                    foreach ($item['itemAttributes'] as $attribute) {
                        $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
                    }
                    $return .= '</ul>';
                }
                if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {

                    $return .= '<div><b>Item Custome Image: </b>' . $item['itemCustomImage'] . '</div>';
                }
                if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {

                    $return .= '<div><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</div>';
                }
                $return .= '</li>';
            }
            $return .= '</ul>';
            $return .= '</li>';
        }
        $return .= '</ul><br /> <br />';
        return $return;
    }

    private function handleSalesOrderItemsHtml($stores = [], $billing = []) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle sales order items Html" , "action_startData" => json_encode($stores) . json_encode($billing)]);
        if (isset($stores['voucher'])) {
            unset($stores['voucher']);
        }
        $return = '<ul>';
        foreach ($stores as $store) {
            $return .= '<li>';
            $return .= '<h3 style="margin-bottom: 0px">From store: (' . $store['storeName'] . ' - ' . $store['storeManagerName'] . ')</h3>';
            $return .= '<div><b>Store Phone/Email: </b>' . $store['storePhone'] . ' / ' . $store['storeEmail'] . '</div>';
            $return .= '<div>Need below items:</div>';
            $return .= '<ul>';
            foreach ($store['items'] as $item) {
                $return .= '<li>';
                $return .= '<div><b>Order Number: </b>' . $billing->billing_number . '</div>';
                $return .= '<div><b>Item Name: </b>' . $item['itemName'] . '</div>';
                $return .= '<div><b>Item Price: </b>' . number_format($item['itemPrice'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
                $return .= '<div><b>Item Quantity: </b>' . $item['itemQuantity'] . '</div>';
                $return .= '<div><b>Total Price: </b>' . number_format($item['itemTotal'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
                $return .= '<div><b>Item Image Link: </b>' . $item['itemImage'] . '</div>';
                if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                    $return .= '<ul>';
                    $return .= '<b>Item specs</b><ul>';
                    foreach ($item['itemAttributes'] as $attribute) {
                        $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
                    }
                    $return .= '</ul>';
                }
                if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != '') {

                    $return .= '<div><b>Item Custome Image: </b>' . $item['itemCustomImage'] . '</div>';
                }
                if (isset($item['itemCustomText']) && $item['itemCustomText'] != '') {

                    $return .= '<div><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</div>';
                }
                $return .= '<div><b>From store address: </b>' . $item['fromPickupAddress'] . ', ' . $item['fromPickupCity'] . ', ' . $item['fromPickupRegion'] . ', ' . $item['fromPickupZipCode'] . '</div>';
                $return .= '</li>';
            }
            $return .= '</ul>';
            $return .= '</li>';
        }
        $return .= '</ul><br /> <br />';
        return $return;
    }

    private function handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle sales order item Html for cancel refund" , "action_startData" => json_encode($purchasedItem) . json_encode($billing)]);
        $return = '<ul>';
        $return .= '<li>';
        $store = PaymentHelper::groupBillMerchantForCancelRefund($purchasedItem);
        $return .= '<h3 style="margin-bottom: 0px">From store: (' . $store['storeName'] . ' - ' . $store['storeManagerName'] . ')</h3>';
        $return .= '<div><b>Store Phone/Email: </b>' . $store['storePhone'] . ' / ' . $store['storeEmail'] . '</div>';
        $return .= '<div>Below item:</div>';
        $return .= '<ul>';
        $item = $store['item'];
        $return .= '<li>';
        $return .= '<div><b>Order Number: </b>' . $billing->billing_number . '</div>';
        $return .= '<div><b>Item Name: </b>' . $item['itemName'] . '</div>';
        $return .= '<div><b>Item Price: </b>' . number_format($item['itemPrice'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
        $return .= '<div><b>Item Quantity: </b>' . $item['itemQuantity'] . '</div>';
        $return .= '<div><b>Total Price: </b>' . number_format($item['itemTotal'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
        $return .= '<div><b>Item Image Link: </b>' . $item['itemImage'] . '</div>';
        if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
            $return .= '<ul>';
            $return .= '<b>Item specs</b><ul>';
            foreach ($item['itemAttributes'] as $attribute) {
                $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
            }
            $return .= '</ul>';
        }
        if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != '') {

            $return .= '<div><b>Item Custome Image: </b>' . $item['itemCustomImage'] . '</div>';
        }
        if (isset($item['itemCustomText']) && $item['itemCustomText'] != '') {

            $return .= '<div><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</div>';
        }
        $return .= '<div><b>From store address: </b>' . $item['fromPickupAddress'] . ', ' . $item['fromPickupCity'] . ', ' . $item['fromPickupRegion'] . ', ' . $item['fromPickupZipCode'] . '</div>';
        $return .= '</li>';
        $return .= '</ul>';
        $return .= '</li>';
        $return .= '</ul><br /> <br />';
        return $return;
    }

    private function handleMerchantOrderItemsHtml($items = [], $billing = []) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle merchant order items Html" , "action_startData" => json_encode($items) . json_encode($billing)]);
        $return = '<ul>';
        foreach ($items as $item) {
            $return .= '<li>';
            $return .= '<div><b>Order Number: </b>' . $billing->billing_number . '</div>';
            $return .= '<div><b>Item Name: </b>' . $item['itemName'] . '</div>';
            $return .= '<div><b>Item Price: </b>' . number_format($item['itemPrice'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
            $return .= '<div><b>Item Quantity: </b>' . $item['itemQuantity'] . '</div>';
            $return .= '<div><b>Total Price: </b>' . number_format($item['itemTotal'], 2) . " " . OlaHubCommonHelper::getTranslatedCurrency($billing->billing_currency) . '</div>';
            $return .= '<div><b>Item Image Link: </b>' . $item['itemImage'] . '</div>';
            if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                $return .= '<ul>';
                $return .= '<b>Item specs</b><ul>';
                foreach ($item['itemAttributes'] as $attribute) {
                    $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
                }
                $return .= '</ul>';
            }
            if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {

                $return .= '<div><b>Item Custome Image: </b>' . $item['itemCustomImage'] . '</div>';
            }
            if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {

                $return .= '<div><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</div>';
            }
            $return .= '<div><b>From your branch address: </b>' . $item['fromPickupAddress'] . ', ' . $item['fromPickupCity'] . ', ' . $item['fromPickupRegion'] . ', ' . $item['fromPickupZipCode'] . '</div>';
            $return .= '</li><br />';
        }
        $return .= '</ul><br />';
        return $return;
    }

    function sendNotRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $password) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user celebration invition Email" , "action_startData" => json_encode($userData) . $celebrationOwner. $celebrationID. "*******"]);
        $template = 'USR015';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendPublishedCelebration($userData, $celebrationName, $celebrationID) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send Published clebration Email" , "action_startData" => json_encode($userData) . $celebrationID]);
        $template = 'USR017';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]', '[CelebrationURL]'];
        $with = [$username, $celebrationName, FRONT_URL . "/celebration/view/" . $celebrationID];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendDeletedCelebration($userData, $celebrationCreator, $celebrationName, $celebrationOwner) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send deleted celebration Email" , "action_startData" => json_encode($userData) . $celebrationOwner]);
        $template = 'USR016';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationCreatorName]', '[CelebrationEvent]', '[CelebrationOwnerName]'];
        $with = [$username, $celebrationCreator, $celebrationName, $celebrationOwner];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $celebrationName) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send register user celebration invition Email" , "action_startData" => json_encode($userData) . $celebrationOwner. $celebrationID. $celebrationName]);
        $template = 'USR014';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAcceptCelebration($userData, $acceptedName, $celebrationName) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send accept celebration Email" , "action_startData" => json_encode($userData) . $acceptedName. $celebrationName]);
        $template = 'USR018';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]'];
        $with = [$acceptedName, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendCommitedCelebration($userData, $celebrationID, $celebrationName) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send commited celebration Email" , "action_startData" => json_encode($userData) . $celebrationID. $celebrationName]);
        $template = 'USR019';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterPublishedCelebrationOwner($userData, $celebrationName, $celebrationID, $password) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send commited celebration Email" , "action_startData" => json_encode($userData) .$celebrationName. $celebrationID. "********"]);
        $template = 'USR020';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[UserPassword]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendScheduleCelebration($userData, $celebrationName, $celebrationID, $celebrationOwner) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send schedule celebration Email" , "action_startData" => json_encode($userData) .$celebrationName. $celebrationID. $celebrationOwner]);
        $template = 'USR021';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[CelebrationOwnerName]'];
        $with = ["$username", FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->email, $celebrationOwner];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterUserGroupInvition($userData, $GroupOwner, $groupID, $password) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user group invition Email" , "action_startData" => json_encode($userData)]);
        $template = 'USR027';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[GroupURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$GroupOwner, FRONT_URL . "/group/" . $groupID, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterUserInvition($userData, $invitorName, $password) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user invition Email" , "action_startData" => json_encode($userData)]);
        $template = 'USR028';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserEmail]', '[UserPassword]', "[OlaHubURL]"];
        $with = [$invitorName, $userData->email, $password, FRONT_URL . "/login"];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNewDesginerRequest($franchise, $designerData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send new desginer request Email" , "action_startData" => json_encode($designerData)]);
        $template = 'ADM007';
        $username = "$franchise->first_name $franchise->last_name";
        $designerName = $designerData->designer_name;
        $designerEmail = $designerData->designer_email;
        $designerPhone = $designerData->designer_phone;
        $replace = ['[desName]', '[desEmail]', '[desPhoneNum]'];
        $with = [$designerName, $designerEmail, $designerPhone];
        $to = [[$franchise->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

}
