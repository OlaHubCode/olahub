<?php

namespace OlaHub\UserPortal\Controllers;

use Illuminate\Support\Facades\Crypt;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\UserBill;
use OlaHub\UserPortal\Models\UserBillDetails;

class PurchasedItemsController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $paymenStatus;

    public function __construct(Request $request)
    {
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
    public function getUserPurchasedItems()
    {
        $log = new \OlaHub\UserPortal\Helpers\Logs();
        $userData = app('session')->get('tempData');
        $payStatusesData = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("is_success", "1")
            ->orWhere("action_id", 0)
            ->orWhere("action_id", 255)->get();
        $payStatusesId = [];
        foreach ($payStatusesData as $statusId) {
            $payStatusesId[] = $statusId->id;
        }
        $purchasedItem = UserBill::whereIn("pay_status", $payStatusesId)->orderBy('id', 'DESC')->paginate(10);
        if ($purchasedItem->count() > 0) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($purchasedItem, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            $log->saveLog($userData->id, $this->requestData, 'Remove Friend');

            return response($return, 200);
        }

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function cancelPurshasedItem($id)
    {
        $user = app('session')->get('tempID');
        $purchasedItem = UserBillDetails::whereHas("mainBill", function ($q) use ($user) {
            $q->where("user_id", $user);
        })->find($id);
        if ($purchasedItem) {
            $bill = $purchasedItem->mainBill;
            $shippingStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($purchasedItem->shipping_status);
            if ((((int)$bill->paid_by == 255 && $this->setPayStatusDataForCash($bill)) || ((int)$bill->paid_by != 255 && $this->setPayStatusData($bill) && $shippingStatus && $shippingStatus->cancel_enabled)) && !$purchasedItem->is_canceled && !$purchasedItem->is_refund && $this->getItemPolicy($purchasedItem, 'cancel')) {
                $purchasedItem->is_canceled = 1;
                $purchasedItem->cancel_date = date("Y-m-d");
                $update = $purchasedItem->save();

                if ($update) {
                    $billingTracking = new \OlaHub\UserPortal\Models\UserBillTracking;
                    $billingTracking->billing_item_id = $purchasedItem->id;
                    $billingTracking->billing_id = $purchasedItem->billing_id;
                    $billingTracking->shipping_status = 16;
                    $billingTracking->save();
                }

                if ($purchasedItem->item_type == 'designer') {
                    $purchasedItem->newPrice = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($purchasedItem->item_price * $purchasedItem->quantity, true, $bill->country_id);
                } else {
                    $purchasedItem->newPrice = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($purchasedItem->item_price * $purchasedItem->quantity, true, $bill->country_id);
                }

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

    public function refundPurshasedItem($id)
    {
        $user = app('session')->get('tempID');
        $purchasedItem = UserBillDetails::whereHas("mainBill", function ($q) use ($user) {
            $q->where("user_id", $user);
        })->find($id);
        if ($purchasedItem) {
            $bill = $purchasedItem->mainBill;
            $shippingStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::find($purchasedItem->shipping_status);
            if ($this->getItemPolicy($purchasedItem, 'refund') && $this->setPayStatusData($bill) && $shippingStatus && $shippingStatus->refund_enabled && !$purchasedItem->is_canceled && !$purchasedItem->is_refund) {
                $purchasedItem->is_refund = 1;
                $purchasedItem->refund_date = date("Y-m-d");
                $update = $purchasedItem->save();

                if ($update) {
                    $billingTracking = new \OlaHub\UserPortal\Models\UserBillTracking;
                    $billingTracking->billing_item_id = $purchasedItem->id;
                    $billingTracking->billing_id = $purchasedItem->billing_id;
                    $billingTracking->shipping_status = 17;
                    $billingTracking->save();
                }

                if ($purchasedItem->item_type == 'designer') {
                    $purchasedItem->newPrice = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setDesignerPrice($purchasedItem->item_price * $purchasedItem->quantity, true, $bill->country_id);
                } else {
                    $purchasedItem->newPrice = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($purchasedItem->item_price * $purchasedItem->quantity, true, $bill->country_id);
                }
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

    private function setPayStatusData($bill)
    {
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

    private function setPayStatusDataForCash($bill)
    {
        $paymentStatusID = $bill->pay_status;
        if ($paymentStatusID > 0 && $paymentStatusID == 13) {
            return true;
        } elseif ($paymentStatusID == 0 && $bill->voucher_used > 0) {
            return true;
        }

        return false;
    }

    private function getItemPolicy($purchasedItem, $type)
    {
        $policy = false;
        switch ($purchasedItem->item_type) {
            case "store":
                $item = \OlaHub\UserPortal\Models\CatalogItem::where("id", $purchasedItem->item_id)->first();
                break;
            case "designer":
                $item = \OlaHub\UserPortal\Models\DesignerItems::where("id", $purchasedItem->item_id)->first();
                break;
        }
        if ($item) {
            $policy = $item->exchangePolicy;
            if ($policy) {
                switch ($type) {
                    case "cancel":
                        $exchange_days = $policy->exchange_days;
                        $allow_exchange = $policy->allow_exchange;
                        if ($allow_exchange && $exchange_days > 0) {
                            $billDate = $purchasedItem->created_at;
                            $allow_date_exchange = date('Y-m-d', strtotime("+" . $exchange_days - 1 . " days", strtotime($billDate)));
                            if (strtotime($allow_date_exchange) >= strtotime(date('Y-m-d'))) {
                                $policy = true;
                            }
                        }
                        break;
                    case "refund":
                        $refund_days = $policy->refund_days;
                        $allow_refund = $policy->allow_refund;
                        if ($allow_refund && $refund_days > 0) {
                            $deliveryDate = $userBillDetail->updated_at;
                            $allow_date_refund = date('Y-m-d', strtotime("+" . $refund_days - 1 . " days", strtotime($deliveryDate)));
                            if (strtotime($allow_date_refund) >= strtotime(date('Y-m-d'))) {
                                $policy = true;
                            }
                        }
                        break;
                }
            }
        }
        return $policy;
    }
    public function getNotRatingBillingItems()
    {
        if (!isset($this->requestData["billing_id"])) {
            return response(['status' => false, 'msg' => 'rightBillingId', 'code' => 406, 'errorData' => []], 200);
        }
        $billing_id = Crypt::decrypt($this->requestData["billing_id"], false);
        $billingItems = UserBillDetails::query()->where('billing_id', $billing_id)->where('is_rated', '=', 0)->get();
        if ($billingItems->count() > 0) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($billingItems, '\OlaHub\UserPortal\ResponseHandlers\BillingItemsResponseHandler');
            $return['status'] = TRUE;
            $return['code'] = 200;
            return response($return, 200);
        }

        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
    public function confirmOrder($id, $status)
    {

        $billing_id = Crypt::decrypt($id, false);

        $order = UserBill::where('id', $billing_id)->first();

        if ($order) {
            $orderData = [
                'billingnumber' => $order->billing_number,
                'billingTotal' => $order->billing_total,
                'payStatus' => $order->pay_status,

                'expired' => true,
            ];
            if ($order->pay_status != 13)
                return (['status' => true,  'code' => 200, 'data' => $orderData]);

            $order->pay_status = $status == 0 ? 15 : 14;
            $order->save();
            $orderData = [
                'billingnumber' => $order->billing_number,
                'payStatus' => $order->pay_status,
                'billingTotal' => $order->billing_total,

            ];
            return (['status' => true, 'msg' => 'confirmed', 'data' => $orderData, 'code' => 200]);
        } else
            return (['status' => false,  'code' => 204]);
    }

    public function trackingOrder($id)
    {

        $user = app('session')->get('tempID');

        $order = UserBill::withoutGlobalScope("currntUser")->where("billing_number", "LIKE", "%" . $id . "%")->first();
        if ($order) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($order, '\OlaHub\UserPortal\ResponseHandlers\TrackingResponseHandler');
            $return['status'] = TRUE;
            $return['msg'] = "getOrderSuccessfully";
            $return['code'] = 200;
            $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
            $logHelper->setLog($this->requestData, $return, 'trackingOrder', $this->userAgent);
            return response($return, 200);
        }
        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        $logHelper->setLog($this->requestData, "No data found", 'trackingOrder', $this->userAgent);
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
