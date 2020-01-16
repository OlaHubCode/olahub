<?php

namespace OlaHub\UserPortal\Helpers;

class CouponsHelper extends OlaHubCommonHelper {

    public $coupon;
    public $couponUser;
    public $couponTotalUsers;
    public $subtotal;
    public $couponData;
    public $saveDiscount = 0;
    public $general = false;

    function checkCouponValid($coupon) {
        $this->coupon = $coupon;
        $today = date("Y-m-d");
        if ($this->coupon->start_date != "0000-00-00" && $this->coupon->start_date > $today) {
            return "promoCodeOfferNotStartYet";
        }
        if ($this->coupon->end_date != "0000-00-00" && $this->coupon->end_date <= $today) {
            return "promoCodeExpire";
        }
        if ($this->coupon->number_of_users == 1) {
            $this->couponUser = \OlaHub\UserPortal\Models\CouponUsers::withoutGlobalScope("couponUser")->where("promo_code_id", $this->coupon->id)->first();
            if ($this->couponUser && $this->couponUser->user_id != app("session")->get("tempID")) {
                return "invalidPromoCode";
            }
        } else {
            $this->couponUser = \OlaHub\UserPortal\Models\CouponUsers::where("promo_code_id", $this->coupon->id)->first();
            $this->couponTotalUsers = \OlaHub\UserPortal\Models\CouponUsers::withoutGlobalScope("couponUser")->where("promo_code_id", $this->coupon->id)->count();
            if (!$this->couponUser) {
                if ($this->coupon->number_of_users == 0) {
                    return "valid";
                } else {
                    if ($this->coupon->number_of_users > $this->couponTotalUsers) {
                        return "valid";
                    } else {
                        return "invalidPromoCode";
                    }
                }
            }
        }
        if ($this->couponUser) {
            $this->couponData = unserialize($this->coupon->coupon_data);
            if ($coupon->code_for == "register" || (isset($this->couponData["couponValueType"]) && $this->couponData["couponValueType"] == "voucher")) {
                return "reachMaxUse";
            } else {
                if ($this->coupon->number_of_use == 0 || $this->coupon->number_of_use > $this->couponUser->number_of_use) {
                    return "valid";
                } else {
                    return "reachMaxUse";
                }
            }
        }
        return "valid";
    }

    public function checkCouponRegister($coupon) {
        $user = app("session")->get("tempData");
        $userRegiseterDate = OlaHubCommonHelper::convertStringToDate($user->created_at, "Y-m-d");
        $this->couponUser = \OlaHub\UserPortal\Models\CouponUsers::where("promo_code_id", $coupon->id)->first();

        if ($user->used_register_code) {
            return ["status" => false, "msg" => "alreadyUsedAnotherPromoCode"];
        }

        if ($this->couponUser) {
            return ["status" => false, "msg" => "alreadyUsed"];
        }

        if (($coupon->start_date != "0000-00-00" && $userRegiseterDate < $coupon->start_date) || ($coupon->end_date != "0000-00-00" && $userRegiseterDate >= $coupon->end_date)) {
            return ["status" => false, "msg" => "invalidPromoCode"];
        }

        $this->couponData = unserialize($coupon->coupon_data);
        $applyCoupon = false;

        if (isset($this->couponData["couponForRegister"]) && $this->couponData["couponForRegister"] == "social") {
            if ($user->facebook_id != NULL && $user->facebook_id != "") {
                $applyCoupon = TRUE;
            }
        } elseif (isset($this->couponData["couponForRegister"]) && $this->couponData["couponForRegister"] == "normal") {
            if ($user->facebook_id == NULL || $user->facebook_id == "") {
                $applyCoupon = TRUE;
            }
        } elseif (!isset($this->couponData["couponForRegister"]) || (isset($this->couponData["couponForRegister"]) && $this->couponData["couponForRegister"] == "all")) {
            $applyCoupon = TRUE;
        }

        if ($applyCoupon) {
            $couponValue = isset($this->couponData["couponVoucherValue"]) ? $this->couponData["couponVoucherValue"] : 0;
            $this->couponUser = new \OlaHub\UserPortal\Models\CouponUsers;
            $this->couponUser->promo_code_id = $coupon->id;
            $this->couponUser->user_id = app("session")->get("tempID");
            $this->couponUser->number_of_use = "1";
            $this->couponUser->save();

            $voucher = \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance(false, $couponValue, false, TRUE);
            $user->used_register_code = "1";
            $user->save();
            return ["status" => TRUE, "msg" => "voucherAdded", "voucher" => $voucher];
        } else {
            return ["status" => FALSE, "msg" => "invalidPromoCode"];
        }
    }

    public function checkCouponCart($coupon, $subtotal, $cart, $recordUse = false) {
        $this->couponData = unserialize($coupon->coupon_data);
        $save = 0;
        if (isset($this->couponData["couponMinCartTotal"]) && $subtotal > $this->couponData["couponMinCartTotal"]) {
            $subtotal = $this->couponData["couponMinCartTotal"];
        }
        $this->checkCartItems($cart, $subtotal);
        if (isset($this->couponData["couponValueType"]) && $this->couponData["couponValueType"] == "voucher") {
            $voucher = isset($this->couponData["couponVoucherValue"]) ? $this->couponData["couponVoucherValue"] : 0;
            if ($this->subtotal >= $voucher) {
                $save = $voucher;
            } else {
                $save = $this->subtotal;
            }
        } else {
            $discount = (isset($this->couponData["couponDiscountValue"]) ? $this->couponData["couponDiscountValue"] : 1) / 100;
            if ($this->subtotal > 0 && $this->general) {
                $save = $this->subtotal * $discount;
            } else {
                $save = $this->saveDiscount;
            }
        }

        if ($recordUse && $save > 0) {
            $this->couponUser = \OlaHub\UserPortal\Models\CouponUsers::where("promo_code_id", $coupon->id)->first();
            if (!$this->couponUser) {
                $this->couponUser = new \OlaHub\UserPortal\Models\CouponUsers;
                $this->couponUser->promo_code_id = $coupon->id;
                $this->couponUser->user_id = app("session")->get("tempID");
                $this->couponUser->number_of_use = 0;
            }
            $this->couponUser->number_of_use += 1;
            $this->couponUser->save();
        }

        return $save;
    }

    private function checkCartItems($cart, $subTotal) {
        $this->subtotal = $subTotal;
        if (isset($this->couponData["couponDiscountOn"])) {
            if ($this->couponData["couponDiscountOn"] != "general") {
                $cartItems = $cart->cartDetails;
                $discountOnVal = is_array($this->couponData["couponDiscountOnValues"]) ? $this->couponData["couponDiscountOnValues"] : [];
                $discount = (isset($this->couponData["couponDiscountValue"]) ? $this->couponData["couponDiscountValue"] : 1) / 100;
                $categories = [];
                foreach ($cartItems as $item) {
                    if ($item->item_type == "store") {
                        if ($cart->celebration_id > 0) {
                            $celebration = \OlaHub\UserPortal\Models\CelebrationModel::find($cart->celebration_id);
                            $mainItem = $item->itemsMainData()->withoutGlobalScope("country")->whereHas('merchant', function ($merchantQ) use($celebration) {
                                $merchantQ->where('country_id', $celebration->country_id);
                            });
                        }else{
                            $mainItem = $item->itemsMainData;
                        }
                    } else {
                        /*
                         * Designer data
                         */
                    }

                    $price = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($mainItem, TRUE, FALSE) * $item->quantity;
                    switch ($this->couponData["couponDiscountOn"]) {
                        case "brand":
                            if (in_array($mainItem->store_id, $discountOnVal)) {
                                $this->saveDiscount = $this->saveDiscount + ($price * $discount);
                            }
                            break;
                        case "designer":
                            if (in_array($mainItem->designer_id, $discountOnVal)) {
                                $this->saveDiscount = $this->saveDiscount + ($price * $discount);
                            }
                            break;
                        case "classification":
                            if (in_array($mainItem->clasification_id, $discountOnVal)) {
                                $this->saveDiscount = $this->saveDiscount + ($price * $discount);
                            }
                            break;
                        case "occasion":
                            $checkOccasions = $this->checkOccasion($mainItem->id, $discountOnVal);
                            if ($checkOccasions) {
                                $this->saveDiscount = $this->saveDiscount + ($price * $discount);
                            }
                            break;
                        case "category":
                            if (count($categories) <= 0) {
                                $categories = $this->getAllCategories($discountOnVal);
                            }
                            if (in_array($mainItem->category_id, $categories)) {
                                $this->saveDiscount = $this->saveDiscount + ($price * $discount);
                            }
                            break;
                        case "interest":
                            break;
                    }
                }
            } else {
                $this->general = TRUE;
            }
        }
    }

    private function getAllCategories($values) {
        $categories = $values;
        $categoriesChilds = \OlaHub\UserPortal\Models\ItemCategory::whereIn("parent_id", $values)->get();
        foreach ($categoriesChilds as $cat) {
            $categories[] = $cat->id;
        }
        return $categories;
    }

    private function checkOccasion($id, $values) {
        $check = FALSE;
        $occasions = \OlaHub\UserPortal\Models\ItemOccasions::where("item_id", $id)->whereIn("occasion_id", $values)->count();
        if ($occasions) {
            $check = TRUE;
        }
        return $check;
    }

}
