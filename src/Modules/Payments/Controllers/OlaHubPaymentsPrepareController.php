<?php

namespace OlaHub\UserPortal\Controllers;

use OlaHub\UserPortal\Controllers\OlaHubPaymentsMainController;
use OlaHub\UserPortal\Models\PaymentMethod;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubPaymentsPrepareController extends OlaHubPaymentsMainController {

    private $vpcConnection;
    private $paymentConfig;

    public function createUserBilling($type = "default") {

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(\OlaHub\UserPortal\Models\UserBill::$columnsMaping, $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $this->setTypeID($type);
        $this->setCartTotal();
        $this->checkPendingBill();
        $this->getUserVoucher();
        $this->checkPayPoint();
        $this->setCurrencyCode();
        $this->getCartDetails();
        if ($this->userVoucher > 0 && $this->total <= $this->userVoucher) {
            $this->voucherUsed = $this->total;
            $this->voucherAfterPay = $this->userVoucher - $this->total;
            $this->finalSave = TRUE;
            $this->createUserBillingHistory("0", "paid_by_voucher");
            $this->updateUserVoucher();
            $this->createUserBillingDetails();
            $this->doVoucherOnlyPaid();
        } else {
            $this->checkCrossCountries();
            $this->getSelectedPaymentMethodDetails($this->cart->country_id);
            $this->prepareBilingActions();
            $this->{'prepare' . ucfirst($this->paymentMethodData->prepare_func)}();
        }

        return response($this->return, 200);
    }

    private function getSelectedPaymentMethodDetails($country) {
        $typeID = $this->typeID;
        $this->paymentMethodData = PaymentMethod::whereHas('typeDataSync', function($query) use($typeID) {
                    $query->where('lkp_payment_method_types.id', $typeID);
                })->whereHas('countryRelation', function($query) use($country) {
                    $query->where('country_id', $country);
                })->find($this->requestData['billGate']);
        if (!$this->paymentMethodData) {
            $this->paymentMethodData = PaymentMethod::whereHas('typeDataSync', function($query) use($typeID) {
                        $query->where('lkp_payment_method_types.id', $typeID);
                    })->where("accept_cross", "1")->has('countryRelation')->find($this->requestData['billGate']);
        }
        $this->paymentMethodCountryData = $this->paymentMethodData->countryRelation()->where('country_id', $country)->first();
    }

    private function prepareBilingActions() {
        if ($this->userVoucher > 0 && $this->total > $this->userVoucher) {
            $this->voucherUsed = $this->userVoucher;
            $this->voucherAfterPay = 0;
            $this->createUserBillingHistory("0_" . $this->paymentMethodData->id);
        } elseif ($this->userVoucher > 0 && ($this->total - $this->paymentMethodCountryData->extra_fees) <= $this->userVoucher) {
            $this->total -= $this->paymentMethodCountryData->extra_fees;
            $this->voucherUsed = $this->total;
            $this->voucherAfterPay = $this->userVoucher - $this->total;
            $this->finalSave = TRUE;
            $this->createUserBillingHistory("0", "paid_by_voucher");
        } else {
            $this->createUserBillingHistory($this->paymentMethodData->id);
        }
        $this->updateUserVoucher();
        $this->createUserBillingDetails();
    }

    private function doVoucherOnlyPaid() {
        $this->finalizeSuccessPayment();
        $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'voucherPaid', 'code' => 200];
        if ($this->celebrationID) {
            $this->return['celebration'] = $this->celebrationID;
        }
    }

    private function prepareMepsSystem() {
        if ($this->finalSave) {
            $this->finalizeSuccessPayment(true, 2);
            $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'voucherPaid', 'code' => 200];
            if ($this->celebrationID) {
                $this->return['celebration'] = $this->celebrationID;
            }
        } else {
            $this->paymentConfig = config('paymentGateway.vpc_config');
            $secureSecret = config('paymentGateway.MigsSecureHashSecret');
            $vpcURL = config('paymentGateway.Migs3PartyBaseUrl');
            $amountKey = config('paymentGateway.vpc_Amount');
            $this->vpcConnection = new \OlaHub\UserPortal\PaymentGates\Helpers\VPCPaymentConnection();
            $this->vpcConnection->setSecureSecret($secureSecret);

            $this->paymentConfig[$amountKey] = round(($this->billing->billing_total - ($this->billing->voucher_used + $this->billing->points_used_curr)), 2) * 1000;
            if ($this->paymentConfig[$amountKey] > 0) {
                $this->setVPCConnection();
                $vpcURL = $this->vpcConnection->getDigitalOrder($vpcURL);
                $this->return = ['status' => true, 'action' => 'redirect', 'redirectLink' => $vpcURL, 'code' => 200];
            }
        }
    }

    private function prepareZainCashSystem() {
        if ($this->finalSave) {
            $this->finalizeSuccessPayment(true, 2);
            $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'paidSuccessfully', 'code' => 200];
            if ($this->celebrationID) {
                $this->return['celebration'] = $this->celebrationID;
            }
        } else {
            $userData = app('session')->get("tempData");
            if (substr($userData->mobile_no, 0, 3) == "079") {

                $userData->mobile_no = substr($userData->mobile_no, 3);
                $userData->mobile_no = "96279" . $userData->mobile_no;
            } elseif (substr($userData->mobile_no, 0, 2) == "00") {
                $userData->mobile_no = substr($userData->mobile_no, 2);
                $userData->mobile_no = $userData->mobile_no;
            }

            try {
                $credit_amountTotal = ($this->billing->billing_total - ($this->billing->voucher_used + $this->billing->points_used_curr));
                $credit_amount = $credit_amountTotal; // - ($credit_amountTotal * 0.10);
                $billingNumber = $this->billing->billing_number . "_" . $this->billing->bill_token;
                $token = (new \OlaHub\UserPortal\PaymentGates\Helpers\ZainConnector)->generateToken($credit_amount, $this->billing->billing_currency, $billingNumber, $userData->mobile_no, $userData->email);
                $url = 'zain/zain.html?token=' . $token->Token;

                $this->return = ['status' => true, 'action' => 'redirect', 'redirectLink' => $url, 'code' => 200];
            } catch (\Exception $e) {
                $this->return = ['status' => false, 'msg' => $e->getMessage(), 'code' => 500];
            }
        }
    }

    private function prepareCashOnDeliverySystem() {
        $this->finalizeSuccessPayment(false, 1, 0);
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesCODRequest($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        if (app('session')->get('tempData')->email) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserCODRequest($this->billing, app('session')->get('tempData'));
        }

        if (app('session')->get('tempData')->mobile_no) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserCODRequest($this->billing, app('session')->get('tempData'));
        }
        $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'CODHasSent', 'code' => 200];
    }

    private function setVPCConnection() {
        $this->paymentConfig[config('paymentGateway.vpc_Currency')] = $this->billing->billing_currency;
        $this->paymentConfig[config('paymentGateway.vpc_MerchTxnRef')] = $this->billing->billing_number . "_" . $this->billing->bill_token;
        $this->paymentConfig[config('paymentGateway.vpc_OrderInfo')] = $this->billing->billing_number . "_" . $this->billing->bill_token;

        ksort($this->paymentConfig);
        foreach ($this->paymentConfig as $key => $value) {
            if (strlen($value) > 0) {
                $this->vpcConnection->addDigitalOrderField($key, $value);
            }
        }
        $secureHash = $this->vpcConnection->hashAllFields();
        $this->vpcConnection->addDigitalOrderField("Title", "Credit card");
        $this->vpcConnection->addDigitalOrderField("vpc_SecureHash", $secureHash);
        $this->vpcConnection->addDigitalOrderField("vpc_SecureHashType", "SHA256");
    }

}
