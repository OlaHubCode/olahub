<?php

namespace OlaHub\UserPortal\Helpers;

class EmailHelper extends OlaHubCommonHelper
{
    protected $style = [
        'thr' => '<hr style="margin: 5px 0;border:0.4px solid #f5f5f5;width:100%" />',
        'fhr' => '<hr style="margin: 0;border:1px solid #eee;width:100%" />',
        'hr' => 'margin: 0;border:1px solid #eee;width:100%',
        'img' => 'border:1px solid #f5f5f5;width:80px;height:80px;object-fit:cover;margin-right:10px',
        'detail_h2' => 'margin:0;font-size:14px',
        'detail_p' => 'margin:3px 0;font-size:12px',
        'merch' => 'margin:3px 0;font-size:12px;color:#888'
    ];
    function sendNewUser($userData, $code)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send new user Email", "action_startData" => json_encode($userData) . $code]);
        $template = 'USR001';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAccountActivationCode($userData, $code)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activation code Email", "action_startData" => json_encode($userData) . $code]);
        $template = 'USR002';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserActivationCode]'];
        $with = [$username, $code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAccountActivated($userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send account activated Email", "action_startData" => json_encode($userData)]);
        $template = 'USR003';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivation($userData, $fullAgent, $code)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation Email", "action_startData" => json_encode($userData) .  $fullAgent . $code]);
        $template = 'USR004';
        $username = "$userData->first_name $userData->last_name";
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent);
        // $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = $userData;
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivationCode($userData, $agent, $code)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activation code Email", "action_startData" => json_encode($userData) .  $agent . $code]);
        $template = 'USR005';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionActivationCode]', '[UserSessionAgent]'];
        $with = [$username, $code, $agent];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSessionActivated($userData, $fullAgent)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send session activated Email", "action_startData" => json_encode($userData) .  $fullAgent]);
        $template = 'USR006';
        $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent);
        // $agent = OlaHubCommonHelper::getUserBrowserAndOS($fullAgent) . " - " . OlaHubCommonHelper::returnCurrentLangField(app('session')->get("def_country"), "name");
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserSessionAgent]'];
        $with = [$username, $agent];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendForgetPassword($userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget password Email", "action_startData" => json_encode($userData)]);
        $template = 'USR007';
        $username = "$userData->first_name $userData->last_name";
        $link = FRONT_URL . "/reset_password?token=$userData->reset_pass_token";
        $replace = ['[UserName]', '[ResetPasswordLink]', '[UserTempCode]'];
        $with = [$username, "<a href='$link'>$link</a>", $userData->reset_pass_code];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendForgetPasswordConfirmation($userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send forget passworf confirmation Email", "action_startData" => json_encode($userData)]);
        $template = 'USR008';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]'];
        $with = [$username];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesCODRequest($billingDetails, $billing, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales COD request Email", "action_startData" => json_encode($billingDetails) . json_encode($billing) . json_encode($userData)]);
        $template = 'SLS003';


        $billingAddress = unserialize($billing->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $billing->billing_currency;
        $orderDetails = $this->handleSalesOrderItemsHtml($billingDetails, $currency);

        $subTotal = 0;
        foreach ($billingDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
        }
        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($billing->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($billing->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($billing->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($billing->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[orderNumber]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderDetails]'];
        $with = [$billing->billing_number, $customerName, $customerPhone, $customerAddress, $orderDetails];
        if (PRODUCTION_LEVEL) {
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        } else {
            $to = [["rami.hashash@olahub.com", "Rami Hashash"]];
        }
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesCancelItem($purchasedItem, $billing, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales cancel item Email", "action_startData" => json_encode($purchasedItem) . json_encode($billing) . json_encode($userData)]);
        $template = 'SLS005';
        $billingAddress = unserialize($billing->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $billing->billing_currency;

        ############# Items #####################
        $orderDetails = $this->style['fhr'];
        $orderDetails .= $this->handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing, $currency);

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[customerName]', '[customerPhone]', '[customerAddress]', '[billingNumber]', '[orderDetails]'];
        $with = [$customerName, $customerPhone, $customerAddress, $billing->billing_number, $orderDetails];
        if (PRODUCTION_LEVEL) {
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        } else {
            $to = [["rami.hashash@olahub.com", "Rami Hashash"]];
        }
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendSalesRefundItem($purchasedItem, $billing, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales refund item Email", "action_startData" => json_encode($purchasedItem) . $billing . json_encode($userData)]);
        $template = 'SLS006';
        $billingAddress = unserialize($billing->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $billing->billing_currency;


        ############# Items #####################
        $orderDetails = $this->style['fhr'];
        $orderDetails .= $this->handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing, $currency);

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[customerName]', '[customerPhone]', '[customerAddress]', '[billingNumber]', '[orderDetails]'];
        $with = [$customerName, $customerPhone, $customerAddress, $billing->billing_number, $orderDetails];
        if (PRODUCTION_LEVEL) {
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        } else {
            $to = [["rami.hashash@olahub.com", "Rami Hashash"]];
        }
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserCODRequest($billing, $userData, $billDetails)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Senduser COD request Email", "action_startData" => json_encode($billing) . json_encode($userData)]);
        $template = 'USR013';
        $username = "$userData->first_name $userData->last_name";
        $currency = $billing->billing_currency;
        ############# Items #####################
        $orderDetails = $this->style['fhr'];
        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails .= $this->handleOrderItemsHtml($store, $currency);
        }

        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($billing->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($billing->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($billing->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($billing->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';
        $replace = ['[userName]', '[orderNumber]', '[orderDetails]'];
        $with = [$username, $billing->billing_number, $orderDetails];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * Direct purchased EMails
     * 
     */

    function sendSalesNewOrderDirect($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order direct Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'SLS001';
        $billingAddress = unserialize($bill->order_address);
        $userName = "$userData->first_name $userData->last_name";
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $bill->billing_currency;
        $orderDetails = $this->handleSalesOrderItemsHtml($billDetails, $currency);

        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
        }
        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($bill->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($bill->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($bill->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($bill->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($bill);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[userName]', '[orderNumber]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderDetails]'];
        $with = [$userName, $bill->billing_number, $customerName, $customerPhone, $customerAddress, $orderDetails];
        if (PRODUCTION_LEVEL) {
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        } else {
            $to = [["rami.hashash@olahub.com", "Rami Hashash"]];
        }
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendMerchantNewOrderDirect($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order direct Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER012';
        $billingAddress = unserialize($bill->order_address);
        $customerName = $billingAddress['full_name'];
        $customerPhone = $billingAddress['phone'];
        $customerAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $bill->billing_currency;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal = 0;
            $merchantName = $store['storeManagerName'];
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails = $this->handleMerchantOrderItemsHtml($store['items'], $bill, $currency);

            ############## Total ####################
            $orderDetails .= "<table style='margin-top:20px;padding-top:10px;width:100%;border-top:1px solid #eee;'>
                <tr><td><b>Total</b> </td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
            $orderDetails .= "</table>";

            ############## Footer ####################
            $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

            $replace = ['[merchantName]', '[orderNumber]', '[customerName]', '[customerPhone]', '[customerAddress]', '[orderDetails]'];
            $with = [$merchantName, $bill->billing_number, $customerName, $customerPhone, $customerAddress, $orderDetails];
            if (PRODUCTION_LEVEL) {
                $to = [[$store['storeEmail'], $store['storeManagerName']]];
            } else {
                $to = [["rami.hashash@olahub.com", $store['storeManagerName']]];
            }
            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendUserNewOrderDirect($userData, $billing, $billDetails)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order direct Email", "action_startData" =>  json_encode($userData) . json_encode($billing)]);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        // $orderDate = date("l, M d, Y", strtotime($billing->billing_date));
        $currency = $billing->billing_currency;
        ############# Items #####################
        // $orderDetails = "<tr><td><b>Date</b> </td><td>$orderDate</td></tr>";
        $orderDetails = $this->style['fhr'];
        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails .= $this->handleOrderItemsHtml($store, $currency);
        }

        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($billing->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($billing->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($billing->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($billing->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';
        $replace = ['[UserName]', '[orderNumber]', '[orderDetails]'];
        $with = [$username, $billing->billing_number, $orderDetails];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserFailPayment($userData, $billing, $reason)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user fail payment Email", "action_startData" =>  json_encode($userData) . json_encode($billing) . $reason]);
        $template = 'USR030';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', "[failReason]"];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . $billing->billing_currency, $reason];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserCancelConfirmation($userData, $item, $billing)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user cancel confirmation Email", "action_startData" =>  json_encode($userData) . json_encode($item) . json_encode($billing)]);
        $template = 'USR031';

        $username = "$userData->first_name $userData->last_name";
        $currency = $billing->billing_currency;
        $store = PaymentHelper::groupBillMerchantForCancelRefund($item);

        $orderDetails = $this->style['fhr'];

        ############# Items #####################
        $orderDetails .= '<table width="100%"><tr>
            <td width="85"><img style="' . $this->style['img'] . '" width="80" src="' .
            \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($item->item_image) . '" /></td>
            <td>
            <h2 style="' . $this->style['detail_h2'] . '">' . $item->item_name . '</h2>
            <p style="' . $this->style['merch'] . '">From : ' . $store['storeName'] . ' - ' . $store['storeManagerName'] . '</p>
            <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item->quantity . '</p>';
        // $attr = @unserialize($item->item_details);
        // if (isset($attr) && is_array($attr) && count($attr)) {
        //     foreach ($attr as $attribute) {
        //         $orderDetails .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName(@$attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName(@$attribute['value']) . '</p>';
        //     }
        // }
        $orderDetails .= '</td>
            <td width="80" align="right"><b>' . number_format($item->item_price, 2) . ' ' . $currency . '</b></td></tr></table>';

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[UserName]', '[orderNumber]', '[orderDetails]'];
        $with = [$username, $billing->billing_number, $orderDetails];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendUserRefundConfirmation($userData, $item, $billing)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user refund confirmation Email", "action_startData" =>  json_encode($userData) . json_encode($item) . json_encode($billing)]);
        $template = 'USR032';
        $username = "$userData->first_name $userData->last_name";
        $currency = $billing->billing_currency;
        $store = PaymentHelper::groupBillMerchantForCancelRefund($item);

        $orderDetails = $this->style['fhr'];

        ############# Items #####################
        $orderDetails .= '<table width="100%"><tr>
            <td width="85"><img style="' . $this->style['img'] . '" width="80" src="' .
            \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($item->item_image) . '" /></td>
            <td>
            <h2 style="' . $this->style['detail_h2'] . '">' . $item->item_name . '</h2>
            <p style="' . $this->style['merch'] . '">From : ' . $store['storeName'] . ' - ' . $store['storeManagerName'] . '</p>
            <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item->quantity . '</p>';
        // $attr = @unserialize($item->item_details);
        // if (isset($attr) && is_array($attr) && count($attr)) {
        //     foreach ($attr as $attribute) {
        //         $orderDetails .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName(@$attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName(@$attribute['value']) . '</p>';
        //     }
        // }
        $orderDetails .= '</td>
            <td width="80" align="right"><b>' . number_format($item->item_price, 2) . ' ' . $currency . '</b></td></tr></table>';

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[UserName]', '[orderNumber]', '[orderDetails]'];
        $with = [$username, $billing->billing_number, $orderDetails];
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

    function sendSalesNewOrderGift($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order gift Email", "action_startData" =>  json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        $template = 'SLS002';
        $billingAddress = unserialize($bill->order_address);
        $userName = "$userData->first_name $userData->last_name";
        $customerPhone = $userData->mobile_no;

        $recipientName = $billingAddress['full_name'];
        $recipientPhone = $billingAddress['phone'];
        $recipientAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $bill->billing_currency;
        $cardMessage = $bill->gift_message;
        $giftDate = OlaHubCommonHelper::convertStringToDate($bill->gift_date);

        ############## Gift ####################
        $orderDetails = '
            <h4 style="margin: 5px 0;">Delivery date: ' . $giftDate . '</h4>
            <p style="margin: 5px 0;"><b>Card message:</b> ' . $cardMessage . '</p>';

        $orderDetails .= $this->handleSalesOrderItemsHtml($billDetails, $currency);

        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
        }

        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($bill->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($bill->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($bill->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($bill->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($bill);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        $replace = ['[userName]', '[orderNumber]', '[customerPhone]', '[recipientName]', '[recipientPhone]', '[recipientAddress]', '[orderDetails]'];
        $with = [$userName, $bill->billing_number, $customerPhone, $recipientName, $recipientPhone, $recipientAddress, $orderDetails];
        if (PRODUCTION_LEVEL) {
            $to = [[JO_SALES_EMAIL, JO_SALES_NAME]];
        } else {
            $to = [["rami.hashash@olahub.com", "Rami Hashash"]];
        }
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendMerchantNewOrderGift($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order gift Email", "action_startData" =>  json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
        if (isset($billDetails['voucher'])) {
            unset($billDetails['voucher']);
        }
        $template = 'MER013';
        $billingAddress = unserialize($bill->order_address);
        $recipientName = $billingAddress['full_name'];
        $recipientPhone = $billingAddress['phone'];
        $recipientAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $bill->billing_currency;
        $giftDate = OlaHubCommonHelper::convertStringToDate($bill->gift_date);

        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal = 0;
            $merchantName = $store['storeManagerName'];
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);

            ############## Gift ####################
            $orderDetails = '<br />
            <h4 style="margin: 5px 0;">Delivery date: ' . $giftDate . '</h4>';
            $orderDetails .= $this->handleMerchantOrderItemsHtml($store['items'], $bill, $currency);

            ############## Total ####################
            $orderDetails .= "<table style='margin-top:20px;padding-top:10px;width:100%;border-top:1px solid #eee;'>
                <tr><td><b>Total</b> </td><td width='80' align='right'><b>" . number_format($subTotal, 2) . " $currency</b></td></tr>";
            $orderDetails .= "</table>";

            ############## Footer ####################
            $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

            $replace = ['[merchantName]', '[orderNumber]', '[recipientName]', '[recipientPhone]', '[recipientAddress]', '[orderDetails]'];
            $with = [$merchantName, $bill->billing_number, $recipientName, $recipientPhone, $recipientAddress, $orderDetails];
            if (PRODUCTION_LEVEL) {
                $to = [[$store['storeEmail'], $store['storeManagerName']]];
            } else {
                $to = [["rami.hashash@olahub.com", $store['storeManagerName']]];
            }
            parent::sendEmail($to, $replace, $with, $template);
        }
    }

    function sendUserNewOrderGift($userData, $billing, $billDetails)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user new order gift Email", "action_startData" => json_encode($userData) . json_encode($billing)]);
        $template = 'USR010';
        $username = "$userData->first_name $userData->last_name";
        $billingAddress = unserialize($billing->order_address);
        $recipientName = $billingAddress['full_name'];
        $recipientPhone = $billingAddress['phone'];
        $recipientAddress = $billingAddress['country'] . ', ' . $billingAddress['city'] . ", " . $billingAddress['address'] . ", " . $billingAddress['zipcode'];
        $currency = $billing->billing_currency;
        $cardMessage = $billing->gift_message;
        $giftDate = OlaHubCommonHelper::convertStringToDate($billing->gift_date);

        ############## Gift ####################
        $orderDetails = '
            <h4 style="margin: 5px 0;">Delivery date: ' . $giftDate . '</h4>
            <p style="margin: 5px 0;"><b>Card message:</b> ' . $cardMessage . '</p>';
        $orderDetails = $this->style['fhr'];
        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails .= $this->handleOrderItemsHtml($store, $currency);
        }

        ############## Total ####################
        $orderDetails .= "<table style='margin-top:20px;width:100%'>
        <tr><td><b>Subtotal</b></td><td width='80' align='right'>" . number_format($subTotal, 2) . " $currency</td></tr>";
        // <td><b>Expected Delivery Date</b> </div><div>#############</div></li>";
        if ($billing->promo_code_saved) {
            $orderDetails .= "<tr><td>
            <b>Promocode discount</b> </td><td width='80' align='right'>" . number_format($billing->promo_code_saved, 2) . " $currency</td></tr>";
        }
        $orderDetails .= "<tr><td>
        <b>Shipping fees</b> </td><td width='80' align='right'>" . number_format($billing->shipping_fees, 2) . " $currency</td></tr>";
        $orderDetails .= "<tr><td colspan='2'>" . $this->style['thr'] . "</td></tr><tr><td>
        <h2>Total</h2> </td><td width='80' align='right'><b>" . number_format($billing->billing_total, 2) . " $currency</b></td></tr>";
        $orderDetails .= "</table>";

        ############## Payments ####################
        $payData = OlaHubCommonHelper::setPayUsed($billing);
        $orderDetails .= "<table style='margin-top:20px;width:100%'>";
        $orderDetails .= "<td colspan='2'><p style='" . $this->style['merch'] . "'>Paid through</p></td>";
        if (isset($payData["orderPayVoucher"])) {
            $orderDetails .= "<tr><td><b>OlaHub balance</b></td><td width='80' align='right'>" . number_format($payData["orderPayVoucher"], 2) . " $currency</td></tr>";
        }
        if (isset($payData["orderPayByGate"])) {
            $orderDetails .= "<tr><td><b>" . $payData["orderPayByGate"] . "</b></td><td width='80' align='right'>" . number_format($payData["orderPayByGateAmount"], 2) . " $currency</td></tr>";
        }
        $orderDetails .= "</table>";

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';
        $replace = ['[UserName]', '[orderNumber]', '[recipientName]', '[recipientPhone]', '[recipientAddress]', '[orderDetails]'];
        $with = [$username, $billing->billing_number, $recipientName, $recipientPhone, $recipientAddress, $orderDetails];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNoneRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send none registered target user order gift Email", "action_startData" => json_encode($userData) . json_encode($billing) .  json_encode($billDetails) . json_encode($target)]);
        $template = 'USR011';
        $username = "$userData->first_name $userData->last_name";
        $currency = $billing->billing_currency;
        $targetName = "$target->first_name $target->last_name";
        $tempPassword = OlaHubCommonHelper::randomString(8, 'str_num');
        $target->password = $tempPassword;
        $target->save();

        ############# Items #####################
        $orderDetails = $this->style['fhr'];
        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails .= $this->handleOrderItemsHtml($store, $currency);
        }

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        // $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $replace = ['[userName]', '[targetName]', '[targetUserName]', '[targetPassword]', '[orderDetails]'];
        $with = [$username, $targetName, $target->mobile_no, $target->password, $orderDetails];
        $to = [[$target->email, $targetName]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendRegisteredTargetUserOrderGift($userData, $billing, $billDetails, $target)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send registered target user order gift Email", "action_startData" => json_encode($userData) . json_encode($billing) .  json_encode($billDetails) . json_encode($target)]);
        $template = 'USR012';
        $username = "$userData->first_name $userData->last_name";
        $currency = $billing->billing_currency;
        $targetName = "$target->first_name $target->last_name";
        $tempPassword = OlaHubCommonHelper::randomString(8, 'str_num');
        $target->password = $tempPassword;
        $target->save();

        ############# Items #####################
        $orderDetails = $this->style['fhr'];
        $subTotal = 0;
        foreach ($billDetails as $store) {
            if (!$store || !isset($store['items']))
                continue;
            $subTotal += $this->handleOrderItemsSubTotal($store['items']);
            $orderDetails .= $this->handleOrderItemsHtml($store, $currency);
        }

        ############## Footer ####################
        $orderDetails .= '<div style="background:#dedede;padding:10px;margin-top:20px;text-align:center;display: block;">
                if you have any question , contact us on (<a href="mailto:info@olahub.com">info@olahub.com</a>)
                <br />
                thank you for your shopping
                </div>';

        // $orderItems = $this->handleUserGiftOrderItemsHtml($billDetails, $billing);
        $replace = ['[userName]', '[targetName]', '[orderDetails]'];
        $with = [$username, $targetName, $orderDetails];
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

    function sendUserPaymentCelebration($userData, $billing)
    {
        $paid_by =  @OlaHubCommonHelper::setPayUsed($billing)['paidBy'];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send user payment celebration Email", "action_startData" => json_encode($userData) . json_encode($billing)]);
        $template = 'USR009';
        $username = "$userData->first_name $userData->last_name";
        $amountCollection = "<div><b>Paid by: </b>$paid_by</div>";
        if ($billing->voucher_used > 0) {
            $amountCollection .= "<div><b>Paid using voucher: </b>" . number_format($billing->voucher_used, 2) . " " . $billing->billing_currency . "</div>";
            $amountCollection .= "<div><b>Voucher after paid: </b>" . number_format($billing->voucher_after_pay, 2) . " " . $billing->billing_currency . "</div>";
        }

        if ($billing->voucher_used > 0 && $billing->billing_total > $billing->voucher_used) {
            $amountCollection .= "<div><b>Paid using ($paid_by): </b>" . number_format(($billing->billing_total - $billing->voucher_used), 2) . " " . $billing->billing_currency . "</div>";
        }
        $replace = ['[UserName]', '[orderNumber]', '[orderAmmount]', '[ammountCollectDetails]'];
        $with = [$username, $billing->billing_number, number_format($billing->billing_total, 2) . " " . $billing->billing_currency, $amountCollection];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    /*
     * 
     * End celebration purchased
     * 
     */

    function sendSalesNewOrderCelebration($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales new order celebration Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
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

    function sendSalesScheduledOrderCelebration($billDetails, $bill, $celebration, $target)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send sales scheduled order celebration Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($celebration) . json_encode($target)]);
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

    function sendMerchantNewOrderCelebration($billDetails, $bill, $userData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant new order clebration Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($userData)]);
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
                    $to = [["rami.hashash@olahub.com", $store['storeManagerName']]];
                }

                parent::sendEmail($to, $replace, $with, $template);
            }
        }
    }

    function sendMerchantScheduledOrderCelebration($billDetails, $bill, $celebration, $target)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send merchant scheduled order clebration Email", "action_startData" => json_encode($billDetails) . json_encode($bill) . json_encode($celebration) . json_encode($target)]);
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
                    $to = [["rami.hashash@olahub.com", $store['storeManagerName']]];
                }

                parent::sendEmail($to, $replace, $with, $template);
            }
        }
    }

    // private function handleUserGiftOrderItemsHtml($stores = [], $billing = [])
    // {
    //     (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handle user gift order items Html", "action_startData" => json_encode($stores) . json_encode($billing)]);
    //     if (isset($stores['voucher'])) {
    //         unset($stores['voucher']);
    //     }
    //     $return = '<ul>';
    //     foreach ($stores as $store) {
    //         $return .= '<li>';
    //         $return .= '<h3 style="margin-bottom: 0px">From store: (' . $store['storeName'] . ' - ' . $store['storeManagerName'] . ')</h3>';
    //         $return .= '<ul>';
    //         foreach ($store['items'] as $item) {
    //             $return .= '<li>';
    //             $return .= '<div><b>Item Name: </b>' . $item['itemName'] . '</div>';
    //             $return .= '<div><b>Item Quantity: </b>' . $item['itemQuantity'] . '</div>';
    //             $return .= '<div><b>Item Image Link: </b>' . $item['itemImage'] . '</div>';
    //             if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
    //                 $return .= '<ul>';
    //                 $return .= '<b>Item specs</b><ul>';
    //                 foreach ($item['itemAttributes'] as $attribute) {
    //                     $return .= '<li><b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': </b>' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</li>';
    //                 }
    //                 $return .= '</ul>';
    //             }
    //             if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {

    //                 $return .= '<div><b>Item Custome Image: </b>' . $item['itemCustomImage'] . '</div>';
    //             }
    //             if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {

    //                 $return .= '<div><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</div>';
    //             }
    //             $return .= '</li>';
    //         }
    //         $return .= '</ul>';
    //         $return .= '</li>';
    //     }
    //     $return .= '</ul><br /> <br />';
    //     return $return;
    // }

    private function handleSalesOrderItemsHtml($stores = [], $currency = "")
    {
        if (isset($stores['voucher'])) {
            unset($stores['voucher']);
        }
        $return = $this->style['fhr'];
        foreach ($stores as $store) {
            $return .= '<h4 style="margin: 5px 0;"><b>From store:</b> ' . $store['storeName'] . ' - ' . $store['storeManagerName'] . '</h4>';
            if (!empty($store['storePhone']))
                $return .= '<h4 style="margin: 5px 0;"><b>Store Phone: </b>' . $store['storePhone'] . '</h4>';
            if (!empty($store['storeEmail']))
                $return .= '<h4 style="margin: 5px 0;"><b>Store Email: </b>' .  $store['storeEmail'] . '</h4>';
            $return .= '<h4 style="margin: 15px 0 5px;">Need below items:</h4>
            <table width="100%">';
            foreach ($store['items'] as $item) {
                $return .= '<tr><td width="85"><img style="' . $this->style['img'] . '" width="80" src="' . $item['itemImage'] . '" /></td>
                <td>
                <h2 style="' . $this->style['detail_h2'] . '">' . $item['itemName'] . '</h2>
                <p style="' . $this->style['merch'] . '">Branch address : ' . $item['fromPickupAddress'] . ', ' . $item['fromPickupCity'] . ', ' . $item['fromPickupRegion'] . ', ' . $item['fromPickupZipCode'] . '</p>
                <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item['itemQuantity'] . '</p>';
                if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                    foreach ($item['itemAttributes'] as $attribute) {
                        $return .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</p>';
                    }
                }
                if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {
                    $return .= '<p><b>Item Custome Image: </b>
                    <a href="' . $item['itemCustomImage'] . '">
                    <img src="' . $item['itemCustomImage'] . '" />
                    </a></p>';
                }
                if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {
                    $return .= '<p><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</p>';
                }
                if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {
                    $return .= '<p><b>Item Custome Image: </b>
                    <a href="' . $item['itemCustomImage'] . '">
                    <img src="' . $item['itemCustomImage'] . '" />
                    </a></p>';
                }
                if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {
                    $return .= '<p><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</p>';
                }
                $return .= '</td>
                <td width="80" align="right"><b>' . $item['itemPrice'] . ' ' . $currency . '</b></td></tr>';
            }
            $return .= '</table><hr style="' . $this->style['hr'] . '" /><br />';
        }
        return $return;
    }

    private function handleSalesOrderItemHtmlForCancelRefund($purchasedItem, $billing, $currency = '')
    {
        $store = PaymentHelper::groupBillMerchantForCancelRefund($purchasedItem);
        $item = $store['item'];

        $return = '<table width="100%"><tr>
            <td width="85"><img style="' . $this->style['img'] . '" width="80" src="' . $item['itemImage'] . '" /></td>
            <td>
            <h2 style="' . $this->style['detail_h2'] . '">' . $item['itemName'] . '</h2>
            <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item['itemQuantity'] . '</p>';
        if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
            foreach ($item['itemAttributes'] as $attribute) {
                $return .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</p>';
            }
        }
        $return .= '</td>
            <td width="80" align="right"><b>' . $item['itemPrice'] . ' ' . $currency . '</b></td></tr>
            <tr><td colspan="3"><br /></td></tr>
            <tr><td colspan="2"><b>Store</b></td><td align="right">' . $store['storeName'] . ' - ' . $store['storeManagerName'] . '</td></tr>
            <tr><td colspan="2"><b>Store address</b></td><td align="right">' . $item['fromPickupAddress'] . ', ' . $item['fromPickupCity'] . ', ' . $item['fromPickupRegion'] . ', ' . $item['fromPickupZipCode'] . '</td></tr>
            <tr><td colspan="2"><b>Store phone</b></td><td align="right">' . $store['storePhone'] . '</td></tr>
            <tr><td colspan="2"><b>Store email</b></td><td align="right">' . $store['storeEmail'] . '</td></tr>
            </table>';

        return $return;
    }

    private function handleOrderItemsHtml($store = [], $currency = "")
    {
        $items = $store['items'];
        $return = "<table width='100%'>";
        foreach ($items as $item) {
            $return .= '<tr><td width="85"><img style="' . $this->style['img'] . '" width="80" src="' . $item['itemImage'] . '" /></td>
            <td>
            <h2 style="' . $this->style['detail_h2'] . '">' . $item['itemName'] . '</h2>
            <p style="' . $this->style['merch'] . '">From : ' . $store['storeManagerName'] . '</p>
            <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item['itemQuantity'] . '</p>';
            if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                foreach ($item['itemAttributes'] as $attribute) {
                    $return .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</p>';
                }
            }
            $return .= '</td>
            <td width="80" align="right"><b>' . $item['itemPrice'] . ' ' . $currency . '</b></td></tr>';
        }
        $return .= '</table>';
        return $return;
    }

    private function handleOrderItemsSubTotal($items = [])
    {
        $total = 0;
        foreach ($items as $item) {
            $total += (float) $item['itemPrice'] * $item['itemQuantity'];
        }
        return number_format($total, 2);
    }

    private function handleMerchantOrderItemsHtml($items = [], $billing = [], $currency = "")
    {
        $return = "<table width='100%'>";
        foreach ($items as $item) {
            $return .= '<tr><td width="85"><img style="' . $this->style['img'] . '" width="80" src="' . $item['itemImage'] . '" /></td>
            <td>
            <h2 style="' . $this->style['detail_h2'] . '">' . $item['itemName'] . '</h2>
            <p style="' . $this->style['merch'] . '">Branch address : ' . $item['fromPickupAddress'] . '</p>
            <p style="' . $this->style['detail_p'] . '">Quantity: ' . $item['itemQuantity'] . '</p>';
            if (isset($item['itemAttributes']) && is_array($item['itemAttributes']) && count($item['itemAttributes'])) {
                foreach ($item['itemAttributes'] as $attribute) {
                    $return .= '<p style="' . $this->style['detail_p'] . '">' . OlaHubCommonHelper::returnCurrentLangName($attribute['name']) . ': ' . OlaHubCommonHelper::returnCurrentLangName($attribute['value']) . '</p>';
                }
            }
            if (isset($item['itemCustomImage']) && $item['itemCustomImage'] != "") {
                $return .= '<p><b>Item Custome Image: </b>
                <a href="' . $item['itemCustomImage'] . '">
                <img src="' . $item['itemCustomImage'] . '" />
                </a></p>';
            }
            if (isset($item['itemCustomText']) && $item['itemCustomText'] != "") {
                $return .= '<p><b>Item Custome Text: </b>' . $item['itemCustomText'] . '</p>';
            }
            $return .= '</td>
            <td width="80" align="right"><b>' . $item['itemPrice'] . ' ' . $currency . '</b></td></tr>';
        }
        $return .= '</table>';
        return $return;
    }

    function sendNotRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $password)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user celebration invition Email", "action_startData" => json_encode($userData) . $celebrationOwner . $celebrationID . "*******"]);
        $template = 'USR015';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendPublishedCelebration($userData, $celebrationName, $celebrationID)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send Published clebration Email", "action_startData" => json_encode($userData) . $celebrationID]);
        $template = 'USR017';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]', '[CelebrationURL]'];
        $with = [$username, $celebrationName, FRONT_URL . "/celebration/view/" . $celebrationID];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendDeletedCelebration($userData, $celebrationCreator, $celebrationName, $celebrationOwner)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send deleted celebration Email", "action_startData" => json_encode($userData) . $celebrationOwner]);
        $template = 'USR016';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationCreatorName]', '[CelebrationEvent]', '[CelebrationOwnerName]'];
        $with = [$username, $celebrationCreator, $celebrationName, $celebrationOwner];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendRegisterUserCelebrationInvition($userData, $celebrationOwner, $celebrationID, $celebrationName)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send register user celebration invition Email", "action_startData" => json_encode($userData) . $celebrationOwner . $celebrationID . $celebrationName]);
        $template = 'USR014';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]'];
        $with = [$celebrationOwner, FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendAcceptCelebration($userData, $acceptedName, $celebrationName)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send accept celebration Email", "action_startData" => json_encode($userData) . $acceptedName . $celebrationName]);
        $template = 'USR018';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationEvent]'];
        $with = [$acceptedName, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendCommitedCelebration($userData, $celebrationID, $celebrationName)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send commited celebration Email", "action_startData" => json_encode($userData) . $celebrationID . $celebrationName]);
        $template = 'USR019';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterPublishedCelebrationOwner($userData, $celebrationName, $celebrationID, $password)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send commited celebration Email", "action_startData" => json_encode($userData) . $celebrationName . $celebrationID . "********"]);
        $template = 'USR020';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[UserPassword]'];
        $with = [FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendScheduleCelebration($userData, $celebrationName, $celebrationID, $celebrationOwner)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send schedule celebration Email", "action_startData" => json_encode($userData) . $celebrationName . $celebrationID . $celebrationOwner]);
        $template = 'USR021';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[CelebrationURL]', '[CelebrationEvent]', '[UserEmail]', '[CelebrationOwnerName]'];
        $with = ["$username", FRONT_URL . "/celebration/view/" . $celebrationID, $celebrationName, $userData->email, $celebrationOwner];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterUserGroupInvition($userData, $GroupOwner, $groupID, $password)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user group invition Email", "action_startData" => json_encode($userData)]);
        $template = 'USR027';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[GroupURL]', '[UserEmail]', '[UserPassword]'];
        $with = [$GroupOwner, FRONT_URL . "/group/" . $groupID, $userData->email, $password];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNotRegisterUserInvition($userData, $invitorName, $password)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send not register user invition Email", "action_startData" => json_encode($userData)]);
        $template = 'USR028';
        $username = "$userData->first_name $userData->last_name";
        $replace = ['[UserName]', '[UserEmail]', '[UserPassword]', "[OlaHubURL]"];
        $with = [$invitorName, $userData->email, $password, FRONT_URL . "/login"];
        $to = [[$userData->email, $username]];
        parent::sendEmail($to, $replace, $with, $template);
    }

    function sendNewDesginerRequest($franchise, $designerData)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Send new desginer request Email", "action_startData" => json_encode($designerData)]);
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
    function sendContactUsEmail($Email)
    {

        $template = 'ADCUS007';
        $userName = "Someone";
        if (strlen($Email['UserName']) > 0) {
            $userName = $Email['UserName'];
        }

        $replace = ['[desName]', '[desEmail]', '[desPhoneNum]', '[Subject]', '[content]'];
        $with = [$userName, $Email['UserEmail'], $Email['UserPhone'], $Email['MessageTitle'], $Email['MessageContent']];

        $to = [["info@olahub.com", "olahub"]];
        parent::sendEmail($to, $replace, $with, $template);
        
    }
}
