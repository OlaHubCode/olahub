<?php

namespace OlaHub\UserPortal\Controllers;

use Illuminate\Support\Facades\Crypt;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\ItemReviews;
use OlaHub\UserPortal\Models\UserBill;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OlaHubItemReviewsController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $itemsModel;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getReviews($slug)
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getReviews"]);
        $reviews = ItemReviews::whereHas('itemMainData', function ($query) use ($slug) {
            $query->where('item_slug', $slug);
            $query->whereNull('parent_item_id');
        })->where('review', "!=", "")->get();
        if ($reviews->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($reviews, '\OlaHub\UserPortal\ResponseHandlers\ItemReviewsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

    public function addReview()
    {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "addReview"]);

        if (
            !isset($this->requestData['billingId']) && !isset($this->requestData['billing'])
            &&  !isset($this->requestData['itemOrderNumber'])
        ) {
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []]]);
            $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []], 200);
        }
        if (isset($this->requestData['billingId']) && isset($this->requestData['billing'])) {
            $billingId = Crypt::decrypt($this->requestData["billingId"], false);

            foreach ($this->requestData['billing'] as $billing) {
                if (isset($billing['userRate']) && isset($billing['itemOrderNumber'])) {
                    $billingItem = \OlaHub\UserPortal\Models\UserBillDetails::whereHas("mainBill", function ($q) use ($billingId) {
                        $q->withoutGlobalScope("currntUser");
                        $q->where("id", $billingId);
                    })->find($billing['itemOrderNumber']);
                    if ($billingItem) {
                        $bill = UserBill::withoutGlobalScope("currntUser")->where('id', $billingItem->billing_id)->first();
                        $shipping = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("review_enabled", 1)->find($billingItem->shipping_status);

                        if ($shipping) {
                            $this->saveReview([
                                'item_id' => $billingItem->item_id,
                                'item_type' => $billingItem->item_type,
                                'userRate' => $billing['userRate'],
                                'userReview' => $billing['userReview'] ?? null,
                            ], false, $bill->user_id);

                            $billingItem->is_rated = 1;
                            $billingItem->save();
                        } else {
                            return response(['status' => false, 'msg' => 'notAbleToReview', 'code' => 500], 200);
                        }
                    } else {
                        throw new NotAcceptableHttpException(404);
                    }
                }
            }

            $return['msg'] = "successRating";
            $return['status'] = true;
            $return['code'] = 200;
            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
        } else {
            if (!isset($this->requestData['userRate']) && !isset($this->requestData['itemOrderNumber'])) {
                $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []]]);
                $log->saveLogSessionData();
                return response(['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []], 200);
            }

            $billingItem = \OlaHub\UserPortal\Models\UserBillDetails::whereHas("mainBill", function ($q) {
                $q->where("user_id", app("session")->get("tempID"));
            })->find($this->requestData["itemOrderNumber"]);
            if (!$billingItem)
                throw new NotAcceptableHttpException(404);
            $shipping = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("review_enabled", "1")->find($billingItem->shipping_status);
            if (!$shipping)
                return response(['status' => false, 'msg' => 'notAbleToReview', 'code' => 500], 200);

            $this->saveReview([
                'item_id' => $billingItem->item_id,
                'item_type' => $billingItem->item_type,
                'userRate' => $this->requestData['userRate'],
                'userReview' => $this->requestData['userReview'] ?? null,
            ]);
            $return['msg'] = "successRating";
            $return['status'] = true;
            $return['code'] = 200;
            $return['data']['reviewRate'] = isset($this->requestData['userRate']) ?  $this->requestData['userRate'] : 0;
            $return['data']['reviewContent'] = isset($this->requestData['userReview']) ?  $this->requestData['userReview'] : "";

            $log->setLogSessionData(['response' => $return]);
            $log->saveLogSessionData();
        }
        return response($return, 200);
    }
    private function saveReview($data, $withReturn = true, $userId = NULL)
    {
        $userId = $withReturn ? app("session")->get("tempID") : $userId;
        $itemReview = new ItemReviews;
        $itemReview->user_id = $userId;
        $itemReview->item_id = $data['item_id'];
        $itemReview->item_type = $data['item_type'];
        $itemReview->rating = $data['userRate'];
        $itemReview->review = $data['userReview'];
        $itemReview->save();
        if ($withReturn) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($itemReview, '\OlaHub\UserPortal\ResponseHandlers\ItemReviewsResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
        }
    }
}
