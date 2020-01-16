<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserBill;
use OlaHub\UserPortal\Models\UserBillDetails;

class PurchasedItemsController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $paymenStatus;

    public function __construct(Request $request) {

        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->authorization = $request->header('Authorization');
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */
    public function getUserPurchasedItems() {
        $payStatusesData = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("is_success", "1")
                ->orWhere("action_id", "255")->get();
        $payStatusesId = [];
        foreach ($payStatusesData as $statusId){
            $payStatusesId[] = $statusId->id;
        }
        $purchasedItem = UserBill::whereIn("pay_status",$payStatusesId)->orderBy('billing_number', 'DESC')->paginate(10);
        if ($purchasedItem->count() > 0) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($purchasedItem, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            $logHelper->setLog($this->requestData, $return, 'getUserPurchasedItems', $this->userAgent);
            return response($return, 200);
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, "No data found", 'getUserPurchasedItems', $this->userAgent);
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function cancelPurshasedItem($id) {
        $user = app('session')->get('tempID');
        $purchasedItem = UserBillDetails::whereHas("mainBill", function ($q) use($user) {
                    $q->where("user_id", $user);
                })->find($id);
        if ($purchasedItem) {
            $bill = $purchasedItem->mainBill;
            $shippingStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($purchasedItem->shipping_status);
            if ($this->setPayStatusData($bill) && $shippingStatus && $shippingStatus->cancel_enabled && !$purchasedItem->is_canceled && !$purchasedItem->is_refund) {
                $purchasedItem->is_canceled = 1;
                $purchasedItem->cancel_date = date("Y-m-d");
                $purchasedItem->save();
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesCancelItem($purchasedItem, $bill, app('session')->get('tempData'));
                if (app('session')->get('tempData')->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserCancelConfirmation(app('session')->get('tempData'), $purchasedItem, $bill);
                }
                if (app('session')->get('tempData')->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserCancelConfirmation(app('session')->get('tempData'), $purchasedItem, $bill);
                }
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($purchasedItem, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');
                $return['status'] = TRUE;
                $return['msg'] = "itemCanceledSuccessfully";
                $return['code'] = 200;
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                $logHelper->setLog($this->requestData, $return, 'getUserPurchasedItems', $this->userAgent);
                return response($return, 200);
            }
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, "No data found", 'getUserPurchasedItems', $this->userAgent);
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function refundPurshasedItem($id) {
        $user = app('session')->get('tempID');
        $purchasedItem = UserBillDetails::whereHas("mainBill", function ($q) use($user) {
                    $q->where("user_id", $user);
                })->find($id);
        if ($purchasedItem) {
            $bill = $purchasedItem->mainBill;
            $shippingStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($purchasedItem->shipping_status);
            if ($this->setPayStatusData($bill) && $shippingStatus && $shippingStatus->refund_enabled && !$purchasedItem->is_canceled && !$purchasedItem->is_refund) {
                $purchasedItem->is_refund = 1;
                $purchasedItem->refund_date = date("Y-m-d");
                $purchasedItem->save();
                (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesRefundItem($purchasedItem, $bill, app('session')->get('tempData'));
                if (app('session')->get('tempData')->email) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserRefundConfirmation(app('session')->get('tempData'), $purchasedItem, $bill);
                }
                if (app('session')->get('tempData')->mobile_no) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserRefundConfirmation(app('session')->get('tempData'), $purchasedItem, $bill);
                }
                $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($purchasedItem, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');
                $return['status'] = TRUE;
                $return['msg'] = "itemRefundedSuccessfully";
                $return['code'] = 200;
                $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
                $logHelper->setLog($this->requestData, $return, 'getUserPurchasedItems', $this->userAgent);
                return response($return, 200);
            }
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, "No data found", 'getUserPurchasedItems', $this->userAgent);
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    private function setPayStatusData($bill) {
        $paymentStatusID = $bill->pay_status;
        if ($paymentStatusID > 0) {
            $this->paymenStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::where('id', $paymentStatusID)->first();
            if ($this->paymenStatus && $this->paymenStatus->shipping_enabled) {
                return true;
            }
        } elseif ($paymentStatusID == 0 && $bill->voucher_used > 0) {
            return true;
        }

        return false;
    }

}
