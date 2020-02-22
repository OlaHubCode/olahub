<?php

namespace OlaHub\UserPortal\Controllers;

use OlaHub\UserPortal\Controllers\OlaHubPaymentsMainController;
use OlaHub\UserPortal\Models\PaymentMethod;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Illuminate\Http\Request;

class OlaHubPaymentsCallbackController extends OlaHubPaymentsMainController
{

    private $billnumber;
    private $billtoken;
    private $request;
    private $paymentMethodID;

    public function callbackUserBilling(Request $request)
    {
        $this->request = $request;
        $requestAll = $request->all();
        if (!$requestAll['vpc_TransactionNo']) {
            return redirect()->to(REDIRECT_FRONT . "/payment-status/" . $requestAll['vpc_Message']);
            // return redirect()->to(REDIRECT_FRONT . '/payment-fail?{paymentFail:true,failMsg:"' . $requestAll['vpc_Message'] . '"}');
        }
        // print_r($requestAll); return "";
        if (isset($requestAll["TransactionId"])) {
            $billnumber = explode("_", $request["TransactionId"]);
        } else {
            $billnumber = explode('_', $this->request->{config('paymentGateway.vpc_MerchTxnRef')});
        }
        //  print_r($requestAll); return "";
        $this->billnumber = $billnumber[0];
        $this->billtoken = $billnumber[1];
        $this->getBillMainData($requestAll);
        $this->setPaymentTypeID();
        $this->getPaymentMethodID();
        $this->getPaymentMethodDetails();
        $this->{'callback' . ucfirst($this->paymentMethodData->call_back_func)}();
        return redirect()->to($this->return);
    }

    private function getBillMainData($requestAll)
    {
        $this->billing = \OlaHub\UserPortal\Models\UserBill::withoutGlobalScope("currntUser")->where('billing_number', $this->billnumber)->first();
        if (!$this->billing) {
            throw new NotAcceptableHttpException(404);
        }
        $check = (new \OlaHub\UserPortal\Helpers\SecureHelper)->verifyPayToken($this->billing, $this->billtoken);
        // if (!$check) {
        //     throw new NotAcceptableHttpException(404);
        // }
        if (!app('session')->get('tempID')) {
            $user = \OlaHub\UserPortal\Models\UserModel::where("id", $this->billing->user_id)->first();
            if (!$user || !$user->is_active) {
                throw new NotAcceptableHttpException(401);
            }
            app('session')->put('tempID', $user->id);
            app('session')->put('tempData', $user);
        }

        $this->billingDetails = $this->billing->billDetails;
        // print_r($this->billingDetails); return "";
        $this->billing->pay_result = serialize($requestAll);
        $this->billing->bill_time = null;
        $this->billing->bill_token = null;
        $this->billing->save();
    }

    protected function getPaymentMethodID()
    {
        $billNum = explode('_', $this->billing->paid_by);
        if ($billNum[0] > 0) {
            $this->paymentMethodID = $billNum[0];
        } elseif (isset($billNum[1]) && $billNum[1] > 0) {
            $this->paymentMethodID = $billNum[1];
        } else {
            throw new NotAcceptableHttpException(404);
        }
    }

    private function getPaymentMethodDetails()
    {
        $this->paymentMethodData = PaymentMethod::withoutGlobalScope('countryScope')->find($this->paymentMethodID);
        if (!$this->paymentMethodData) {
            throw new NotAcceptableHttpException(404);
        }
    }

    private function callbackMepsSystem()
    {
        $this->vpcConnection = new \OlaHub\UserPortal\PaymentGates\Helpers\VPCPaymentConnection();
        $secureSecret = config('paymentGateway.MigsSecureHashSecret');

        $this->vpcConnection->setSecureSecret($secureSecret);
        $errorExists = false;

        foreach ($this->request->all() as $key => $value) {
            if (($key != "vpc_SecureHash") && ($key != "vpc_SecureHashType") && ((substr($key, 0, 4) == "vpc_") || (substr($key, 0, 5) == "user_"))) {
                $this->vpcConnection->addDigitalOrderField($key, $value);
            }
        }

        $serverSecureHash = array_key_exists("vpc_SecureHash", $this->request->all()) ? $this->request->vpc_SecureHash : "";
        $secureHash = $this->vpcConnection->hashAllFields();
        if ($secureHash == $serverSecureHash) {
            $hashValidated = TRUE;
        } else {
            $hashValidated = FALSE;
            $errorExists = true;
        }

        $txnResponseCode = array_key_exists("vpc_TxnResponseCode", $this->request->all()) ? $this->request->vpc_TxnResponseCode : "";
        $cscResultCode = array_key_exists("vpc_CSCResultCode", $this->request->all()) ? $this->request->vpc_CSCResultCode : "";
        $txnResponseCodeDesc = "";
        $cscResultCodeDesc = "";

        if ($txnResponseCode != "No Value Returned") {
            $txnResponseCodeDesc = \OlaHub\UserPortal\PaymentGates\Helpers\PaymentCodesHelper::getResultDescription($txnResponseCode);
        }

        if ($cscResultCode != "No Value Returned") {
            $cscResultCodeDesc = \OlaHub\UserPortal\PaymentGates\Helpers\PaymentCodesHelper::getCSCResultDescription($cscResultCode);
        }

        $this->return = REDIRECT_FRONT . "/payment-status/";

        if ($txnResponseCode != "0" || $errorExists) {
            $this->finalizeFailPayment($txnResponseCodeDesc);
            $this->return .= 'fail';
            // $this->return = ['status' => true, 'action' => 'fail', 'msg' => 'paymentFail', 'code' => 200];
            // if ($this->celebrationID) {
            //     $this->return['celebration'] = $this->celebrationID;
            // }
        } else {
            $this->finalizeSuccessPayment(true, 2);
            $this->return .= 'paid';
            // $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'paidSuccessfully', 'code' => 200];
            // if ($this->celebrationID) {
            //     $this->return['celebration'] = $this->celebrationID;
            // }
        }
        if ($this->celebrationID)
            $this->return .= '/' . $this->celebrationID;
    }

    function callbackZainCashSystem()
    {
        $this->return = REDIRECT_FRONT . "/payment-status/";
        $data = $this->request->all();
        $returnResult = (new \OlaHub\UserPortal\PaymentGates\Helpers\ZainConnector)->getTokenStatus($data["Token"]);
        $result = (new \OlaHub\UserPortal\PaymentGates\Helpers\ZainConnector)->paymentDone($returnResult);
        $resultJson = json_decode($result);
        if (array_key_exists("error", $resultJson)) {
            $this->return .= 'fail';
            $this->finalizeFailPayment("Unkown error");
        } else {
            $this->finalizeSuccessPayment(true, 2);
            $this->return .= 'paid';
            // $this->return = ['status' => true, 'action' => 'paid', 'msg' => 'paidSuccessfully', 'code' => 200];
            if ($this->celebrationID) {
                $this->return .= '/' . $this->celebrationID;
                // $this->return['celebration'] = $this->celebrationID;
            }
        }
    }

    private function setPaymentTypeID()
    {
        if ($this->billing->pay_for > 0) {
            $this->typeID = 3;
            $this->celebrationID = $this->billing->pay_for;
            $this->getCelebrationDetails();
        } elseif ($this->billing->is_gift) {
            $this->typeID = 2;
        } else {
            $this->typeID = 1;
        }
    }
}
