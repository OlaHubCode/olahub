<?php

namespace OlaHub\UserPortal\Helpers;

use Illuminate\Support\Facades\Crypt;

class SmsHelper extends OlaHubCommonHelper
{

    public $countryCode = "";
    private function handleNumbers($number){
        $number = str_replace(",", "", $number);
        return number_format($number, 2, ".", ",");
    }
    private function getCountryCode($country_id)
    {
        $country = \OlaHub\UserPortal\Models\ShippingCountries::where('olahub_country_id', $country_id)->first();
        if (!empty($country->phonecode)) {
            $this->countryCode = $country->phonecode;
        }
    }

    function sendNewUser($userData, $code)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR001';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAccountActivationCode($userData, $code)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR002';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAccountActivated($userData)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR003';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivation($userData, $fullAgent, $code)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR004';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent);
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivationCode($userData, $agent, $code)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR005';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendSessionActivated($userData, $fullAgent)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR006';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent);
        $replace = ['[UserName]', '[UserSessionAgent]'];
        $with = [$username, $agent];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendForgetPassword($userData)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR007';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[ResetPasswordLink]', '[UserTempCode]'];
        $with = [$username, FRONT_URL . "/reset_password?token=$userData->reset_pass_token", $userData->reset_pass_code];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendForgetPasswordConfirmation($userData)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR008';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $password)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR015';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $this->countryCode . (int) $userData->mobile_no, $password];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendPublishedCelebration($userData, $celebrationName, $celebrationID)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR017';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]', '[CelebrationURL]'];
        $with = [$username, $celebrationName, FRONT_URL . "/celebration/view/" . $celebrationID];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserCODRequest($billing, $userData)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR013';
        $userName = "$userData->first_name $userData->last_name";
        $billingNumber = $billing->billing_number;
        $totalAmount = $this->handleNumbers(($billing->billing_total + $billing->billing_fees - $billing->voucher_used)) . " " . $billing->billing_currency;
        $replace = ['[userName]', '[orderNumber]', '[orderAmmount]'];
        $with = [$userName, $billingNumber, $totalAmount];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendDeletedCelebration($userData, $celebrationCreator, $celebrationName, $celebrationOwner)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR016';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationCreatorName]', '[CelebrationEvent]', '[CelebrationOwnerName]'];
        $with = [$username, $celebrationCreator, $celebrationName, $celebrationOwner];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $celebrationName)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR014';
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendAcceptCelebration($userData, $acceptedName, $celebrationName)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR018';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]'];
        $with = [$acceptedName, $celebrationName];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendCommitedCelebration($userData, $celebrationID, $celebrationName)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR019';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterPublishedCelebrationOwner($userData, $celebrationName, $celebrationID, $password)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR020';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[UserPassword]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $this->countryCode . (int) $userData->mobile_no, $password];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendScheduleCelebration($userData, $celebrationName, $celebrationID, $celebrationOwner)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR021';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[CelebrationOwnerName]'];
        $with = ["$username", FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $this->countryCode . (int) $userData->mobile_no, $celebrationOwner];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserNewOrderDirect($userData, $billing)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "Paid by: " . $payData["paidBy"];
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "
                    Paid using voucher: " . $this->handleNumbers($payData["orderPayVoucher"]) . " " . $billing->billing_currency;
            $amountCollection .= "
                    Voucher after paid: " . $this->handleNumbers($payData["orderVoucherAfterPay"]) . " " . $billing->billing_currency;
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "
                    Paid using (" . $payData["orderPayByGate"] . "): </b>" . $this->handleNumbers(($payData["orderPayByGateAmount"])) . " " . $billing->billing_currency;
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, $this->handleNumbers($billing->billing_total) . " " . $billing->billing_currency, $amountCollection];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserNewOrderGift($userData, $billing)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR010';
        $username = "$userData->first_name $userData->last_name";
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $amountCollection = "Paid by: " . $payData["paidBy"];
        if (isset($payData["orderPayVoucher"])) {
            $amountCollection .= "
                    Paid using voucher: " . $this->handleNumbers($payData["orderPayVoucher"]) . " " . $billing->billing_currency;
            $amountCollection .= "
                    Voucher after paid: " . $this->handleNumbers($payData["orderVoucherAfterPay"]) . " " . $billing->billing_currency;
        }

        if (isset($payData["orderPayByGate"])) {
            $amountCollection .= "
                    Paid using (" . $payData["orderPayByGate"] . "): </b>" . $this->handleNumbers(($payData["orderPayByGateAmount"])) . " " . $billing->billing_currency;
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, $this->handleNumbers($billing->billing_total) . " " . $billing->billing_currency, $amountCollection];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNoneRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target)
    {
        $this->getCountryCode($target->country_id);
        $template = 'USR011';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $tempPassword = OlaHubCommonHelper::randomString(8, 'str_num');
        $target->password = $tempPassword;
        $target->save();
        $replace = ['[userName]', '[giftsOrder]', '[targetUserName]', '[targetPassword]'];
        $with = [$username, $orderItems, $this->countryCode . (int) $target->mobile_no, $tempPassword];
        $to = $this->countryCode . (int) $target->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target)
    {
        $this->getCountryCode($target->country_id);
        $template = 'USR012';
        $username = "$userData->first_name $userData->last_name";
        $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $targetName = "$target->first_name $target->last_name";
        $replace = ['[userName]', '[giftsOrder]'];
        $with = [$username, $orderItems];
        $to = $this->countryCode . (int) $target->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    private function handleUserGiftOrderItemsHtml($stores = [], $billing = [])
    {
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

    function sendNotRegisterUserGroupInvition($userData, $GroupOwner, $groupID, $password)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR027';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[GroupURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$GroupOwner, FRONT_URL . "/group/" . $groupID, $this->countryCode . (int) $userData->mobile_no, $password];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterUserInvition($userData, $invitorName, $password)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR028';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserEmail]', '[UserPassword]', "[OlaHubURL]"];
        $with = [$invitorName, $this->countryCode . (int) $userData->mobile_no, $password, FRONT_URL . "/login"];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserFailPayment($userData, $billing, $reason)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR030';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', "[failReason]"];
        $with = [$username, $billing->billing_number, $this->handleNumbers($billing->billing_total) . " " . $billing->billing_currency, $reason];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserCancelConfirmation($userData, $item, $billing)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR031';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, $item->newPrice, $item->item_name];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendUserRefundConfirmation($userData, $item, $billing)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR032';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[itemAmmount]', "[itemName]"];
        $with = [$username, $billing->billing_number, $item->newPrice, $item->item_name];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }
    function sendDeletedRegistry($userData, $registryName, $registryOwner)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR033';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[RegistryEvent]', '[RegistryOwnerName]'];
        $with = [$username, $registryName, $registryOwner];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendRegisterUserRegistryInvition($userData, $registryOwner, $registryID, $registryName)
    {
        $this->getCountryCode($userData->country_id);
        $template = 'USR034';
        $replace = ['[UserName]', '[RegistryURL]', '[RegistryEvent]'];
        $with = [$registryOwner, FRONT_URL . "/registry/view/" . $registryID, $registryName];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }

    function sendNotRegisterUserRegistryInvition($mobile, $registryOwner, $registryID, $registryName)
    {
        $template = 'USR034';
        $replace = ['[UserName]', '[RegistryURL]', '[RegistryEvent]'];
        $with = [$registryOwner, FRONT_URL . "/registry/view/" . $registryID, $registryName];
        $to = (int) $mobile;
        parent::sendSms($to, $replace, $with, $template);
    }
    function sendNoneRatingAndReview($userData, $billing_id)
    {
        $this->getCountryCode($userData->country_id);
        $username = "$userData->first_name $userData->last_name";
        $billing_id = Crypt::encrypt($billing_id, false);
        $template = 'USR036';
        $replace = ['[UserName]', '[RatingURL]'];
        $with = [$username, FRONT_URL . "/rating?" . $billing_id];
        $to = $this->countryCode . (int) $userData->mobile_no;
        parent::sendSms($to, $replace, $with, $template);
    }
}
