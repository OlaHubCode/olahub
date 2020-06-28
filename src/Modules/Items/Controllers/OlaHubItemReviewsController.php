<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\ItemReviews;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OlaHubItemReviewsController extends BaseController {

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    protected $itemsModel;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    public function getReviews($slug) {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "getReviews"]);
       
        $reviews = ItemReviews::whereHas('itemMainData', function($query) use($slug) {
                    $query->where('item_slug', $slug);
                    $query->whereNull('parent_item_id');
                })->where('review',"!=", "")->get();
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

    public function addReview() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Items", 'function_name' => "addReview"]);
       
        if(!isset($this->requestData['userRate']) && !isset($this->requestData['userReview']) && !isset($this->requestData['itemOrderNumber']) ){
            $log->setLogSessionData(['response' => ['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []]]);
        $log->saveLogSessionData();
            return response(['status' => false, 'msg' => 'setRateReview', 'code' => 406, 'errorData' => []], 200);
        }
        $billingItem = \OlaHub\UserPortal\Models\UserBillDetails::whereHas("mainBill", function ($q){
            $q->where("user_id", app("session")->get("tempID"));
        })->find($this->requestData["itemOrderNumber"]); 
        if (!$billingItem) {
            throw new NotAcceptableHttpException(404);
        }
        
        $shipping = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("review_enabled", "1")->find($billingItem->shipping_status);
        if(!$shipping){
            return response(['status' => false, 'msg' => 'notAbleToReview', 'code' => 500], 200);
        }
        $itemReview = new ItemReviews;
        
        $itemReview->item_id = $billingItem->item_id;
        $itemReview->item_type = $billingItem->item_type;
        $itemReview->rating = isset($this->requestData['userRate']) && $this->requestData['userRate'] ? $this->requestData['userRate'] : 0;
        $itemReview->review = isset($this->requestData['userReview']) && $this->requestData['userReview'] ? $this->requestData['userReview'] : "";
        if ($itemReview->save()) {
            $return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($itemReview, '\OlaHub\UserPortal\ResponseHandlers\ItemReviewsResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
        }else{
            throw new BadRequestHttpException(500);
        }
        $log->setLogSessionData(['response' => $return]);
        $log->saveLogSessionData();
        return response($return, 200);
    }

}
