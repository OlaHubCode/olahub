<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\Coupon;

class OlaHubCouponsController extends BaseController {

    protected $requestData;
    protected $return;

    public function __construct(Request $request) {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->return = ['status' => false, 'msg' => 'invalidPromoCode'];
    }

    public function checkCouponForUser() {
        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['module_name' => "Coupons", 'function_name' => "checkCouponForUser"]);
       
        if (isset($this->requestData["coupon"]) && strlen($this->requestData["coupon"]) > 4) {
            $coupon = Coupon::where("unique_code", $this->requestData["coupon"])->first();
            if ($coupon) {
                $couponHelper = new \OlaHub\UserPortal\Helpers\CouponsHelper;
                $couponValid = $couponHelper->checkCouponValid($coupon);
                if ($couponValid == "valid") {
                    if ($coupon->code_for == "register") {
                        $checkRegisterCoupon = $couponHelper->checkCouponRegister($coupon);
                        $this->return = $checkRegisterCoupon;
                    } else {
                        $userCart = \OlaHub\UserPortal\Models\Cart::getUserCart();
                        $userCart->promo_code_id = $coupon->id;
                        $userCart->save();
                        $this->return = ["status" => TRUE, "msg" => "promoCodeAddedToCart", "cart" => "1"];
                    }
                } else {
                    $this->return = ['status' => false, 'msg' => $couponValid];
                }
            }
        }
        $log->setLogSessionData(['response' => $this->return]);
        $log->saveLogSessionData();
        return response($this->return, 200);
    }

}
