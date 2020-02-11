<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\PaymentMethod;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use OlaHub\UserPortal\Models\UserModel;

class OlaHubPaymentsMainController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $return;
    protected $typeID;
    protected $userVoucherAccount;
    protected $userVoucher = 0;
    protected $voucherUsed = 0;
    protected $pointsUsedCurr = 0;
    protected $pointsUsedInt = 0;
    protected $voucherAfterPay = 0;
    protected $billing;
    protected $billingDetails;
    protected $cart;
    protected $cartTotal;
    protected $cartDetails;
    protected $shippingFees = 0;
    protected $cashOnDeliver = 0;
    protected $promoCodeSave = 0;
    protected $total;
    protected $currency;
    protected $celebrationID;
    protected $celebrationDetails;
    protected $participant;
    protected $paymentMethodData;
    protected $paymentMethodCountryData;
    protected $grouppedMers;
    protected $finalSave = false;
    protected $crossCountry = false;
    protected $cartModel;
    protected $id;
    protected $userId;
    protected $celebration;
    protected $userMongo;
    protected $friends;
    protected $calendar;

    public function __construct(Request $request)
    {
        $this->return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $this->return['requestData'];
        $this->requestFilter = $this->return['requestFilter'];
        $this->return = ['status' => false, 'message' => 'Some data is wrong'];
        $this->id = isset($this->requestData["valueID"]) && $this->requestData["valueID"] > 0 ? $this->requestData["valueID"] : false;
        $this->userId = app('session')->get('tempID') > 0 ? app('session')->get('tempID') : false;
        $this->celebration = false;
        $this->calendar = false;
        $this->cart = false;
    }

    public function getPaymentsList($type = "default")
    {
        $checkPermission = $this->checkActionPermission($type);
        if (isset($checkPermission['status']) && !$checkPermission['status']) {
            return response($checkPermission, 200);
        }
        $this->checkCart($type);
        $this->typeID = $this->requestData['paymentType'];
        $this->setCartTotal(true);
        $this->checkPendingBill();
        $this->getUserVoucher();
        $this->checkPayPoint();
        if ($this->userVoucher > 0 && $this->total <= $this->userVoucher) {
            $this->return["proceed"] = 1;
        } else {
            $this->checkCrossCountries();
            $this->getPaymentMethodsDetails($this->cart->country_id);
            $this->return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($this->paymentMethodCountryData, '\OlaHub\UserPortal\ResponseHandlers\PaymentResponseHandler');
        }
        $this->return['status'] = true;
        $this->return['code'] = 200;
        if ($this->typeID == '1' || !($this->cart->for_friend > 0)) {
            $this->return['shippingAddress'] = \OlaHub\UserPortal\Models\UserShippingAddressModel::checkUserShippingAddress(app('session')->get('tempID'), $this->cart->country_id);
        } elseif ($this->typeID == '2' && $this->cart->for_friend > 0) {
            $this->return['shippingAddress'] = \OlaHub\UserPortal\Models\UserShippingAddressModel::checkUserShippingAddress($this->cart->for_friend, $this->cart->country_id);
        } else {
            $this->return['shippingAddress'] = [];
        }
        $country = \OlaHub\UserPortal\Models\ShippingCountries::where('olahub_country_id', $this->cart->country_id)->first();

        // $regions = \OlaHub\UserPortal\Models\ShippingRegions::where('country_id', $country->id)->get();
        // $reg = [];
        // foreach ($regions as $region) {
        //     $reg[] = [
        //         'key' => $region->id,
        //         'text' => $region->name,
        //         'value' => $region->id,
        //     ];
        // }
        // $this->return['regions'] = $reg;

        $shippingFees = $this->cart->cartDetails()->whereHas('itemsMainData', function ($q) {
            $q->where('is_shipment_free', '1');
        })->first() ? SHIPPING_FEES : 0;
        $shippingFees += $shippingFees == 0 ? \OlaHub\UserPortal\Models\Cart::checkDesignersShipping($this->cart) : 0;
        $this->return['shippingFees'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($shippingFees);

        return response($this->return, 200);
    }

    function checkPayPoint()
    {
        $points = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as total_points')->first();
        $this->pointsUsedInt = $points->total_points;
        if ($this->billing && $this->billing->points_used > 0) {
            $this->pointsUsedInt += $this->billing->points_used;
            $userInsert = new \OlaHub\UserPortal\Models\UserPoints;
            $userInsert->country_id = app('session')->get('def_country')->id;
            $userInsert->campign_id = 0;
            $userInsert->points_collected = $this->billing->points_used;
            $userInsert->collect_date = date("Y-m-d");
            $userInsert->save();
        }
        if ($this->pointsUsedInt > 0) {
            $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
            $this->pointsUsedCurr = $this->pointsUsedInt * $exchangeRate->sell_price;
            $this->userVoucher += $this->pointsUsedCurr;
        }

        /* $requestPoints = isset($this->requestData['pay_points']) ? $this->requestData['pay_points'] : false;
          if ($requestPoints == 1) {

          if ($points) {



          $pointsUsed = 0;
          if ($total > $userReedem) {
          $this->total = $this->total - $userReedem;
          $pointsUsed = $totalPoints;
          } else {
          $this->total = 0;
          $pointsUsed = $this->total / $exchangeRate->sell_price;
          }

          $userInsert = new \OlaHub\UserPortal\Models\UserPoints;
          $userInsert->country_id = app('session')->get('def_country')->id;
          $userInsert->campign_id = 0;
          $userInsert->points_collected = "-" . $pointsUsed;
          $userInsert->collect_date = date("Y-m-d");
          $userInsert->save();
          }
          } */
    }

    protected function checkCrossCountries()
    {
        $getIPInfo = new \OlaHub\UserPortal\Helpers\getIPInfo();
        $countryCode = $getIPInfo->ipData('countrycode');
        $countryID = 0;
        if ($countryCode && strlen($countryCode) == 2) {
            $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $countryCode)->where('is_supported', '1')->where('is_published', '1')->first();
            if ($country) {
                $countryID = $country->id;
            }
        }
        if ($countryID != $this->cart->country_id) {
            $this->crossCountry = true;
        }
    }

    protected function getCartDetails()
    {
        $this->cartDetails = $this->cart->cartDetails()->get();
        if (!($this->cartDetails->count() > 0)) {
            throw new NotAcceptableHttpException(404);
        }
    }

    protected function checkPromoCode($cartSubTotal, $recordUse = true)
    {
        if ($this->cart->promo_code_id) {
            $coupon = \OlaHub\UserPortal\Models\Coupon::find($this->cart->promo_code_id);
            if ($coupon) {
                $checkValid = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponValid($coupon);
                if ($checkValid == "valid") {
                    if ($coupon->code_for == "cart") {
                        $this->promoCodeSave = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponCart($coupon, $cartSubTotal, $this->cart, $recordUse);
                    }
                }
            }
        }
    }

    protected function setCartTotal($withExtra = true)
    {
        $this->cartTotal = (float) \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
        $this->checkPromoCode($this->cartTotal, $withExtra);
        if ($withExtra) {
            $this->shippingFees = $this->cart->cartDetails()->whereHas('itemsMainData', function ($q) {
                $q->where('is_shipment_free', '1');
            })->first() ? SHIPPING_FEES : 0;
            $this->shippingFees += $this->shippingFees == 0 ? \OlaHub\UserPortal\Models\Cart::checkDesignersShipping($this->cart) : 0;
            // $this->shippingFees += \OlaHub\UserPortal\Models\Cart::checkDesignersShipping($this->cart);
            if ($this->paymentMethodCountryData) {
                $this->cashOnDeliver = $this->paymentMethodCountryData->extra_fees;
            }
        }
        if ($this->celebration) {
            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)
                ->where('user_id', app('session')->get('tempID'))->first();
            $this->shippingFees = SHIPPING_FEES;
            $pp = $this->celebration->celebrationParticipants->count();
            $debt = ($this->shippingFees / $pp);
            $debt = number_format($debt - fmod($debt, MOD_CELEBRATION), 2, ".", "");
            $this->shippingFees = ($this->shippingFees == ($debt * $pp) ? $debt : number_format(($this->shippingFees - ($debt * $pp)) + $debt, 2, ".", ""));
            $this->cartTotal = $participant->amount_to_pay - $this->shippingFees;

            // $participants = $this->celebration->celebrationParticipants;
            // $participantAmount = $this->cartTotal / $participants->count();
            // $reminder = $this->cartTotal - ($participantAmount * $participants->count());
            // if ($reminder > 0 && $this->celebrationDetails->created_by == app('session')->get('tempID')) {
            //     $participantAmount = $participantAmount + $reminder;
            // }
            // if ($this->shippingFees > 0) {
            //     $this->shippingFees = $this->shippingFees / $participants->count();
            // }
            // if ($this->cashOnDeliver && $this->cashOnDeliver > 0) {
            //     $this->cashOnDeliver = $this->cashOnDeliver / $participants->count();
            // }
        }
        $this->total = (float) $this->cartTotal + $this->shippingFees + $this->cashOnDeliver - $this->promoCodeSave;
        // echo $this->total;
    }

    protected function getPaymentMethodsDetails($country)
    {
        $typeID = $this->typeID;
        if ($this->crossCountry) {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::where('country_id', $country)
                ->whereHas('PaymentData', function ($q) use ($typeID) {
                    $q->whereHas('typeDataSync', function ($query) use ($typeID) {
                        $query->where('lkp_payment_method_types.id', $typeID);
                    });
                })
                ->where('is_cross', '1')
                ->get();
        } else {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::where('country_id', $country)
                ->whereHas('PaymentData', function ($q) use ($typeID) {
                    $q->whereHas('typeDataSync', function ($query) use ($typeID) {
                        $query->where('lkp_payment_method_types.id', $typeID);
                    });
                })->groupBy("payment_method_id")->get();
        }

        if (!$this->paymentMethodCountryData->count()) {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::whereHas('PaymentData', function ($q) use ($typeID) {
                $q->where("accept_cross", "1");
                $q->whereHas('typeDataSync', function ($query) use ($typeID) {
                    $query->where('lkp_payment_method_types.id', $typeID);
                });
            })->get();
        }
    }

    protected function checkUserBalanceCover()
    {
        if ($this->total > $this->userVoucher) {
            $this->voucherAfterPay = 0;
            $this->voucherUsed = $this->userVoucher;
        } elseif ($this->userVoucher > 0 && $this->total <= $this->userVoucher) {
            $this->voucherUsed = $this->total;
            $this->voucherAfterPay = $this->userVoucher - $this->total;
            $this->finalSave = TRUE;
        }
    }

    protected function checkPendingBill()
    {
        $pendingStatusIDs = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("cycle_order", 1)->where("action_id", ">", 0)->get();
        $pendingIds = [];
        foreach ($pendingStatusIDs as $one) {
            $pendingIds[] = $one->id;
        }
        $this->billing = \OlaHub\UserPortal\Models\UserBill::where('temp_cart_id', $this->cart->id)->where('country_id', $this->cart->country_id)->whereIn('pay_status', $pendingIds)->first();
    }

    protected function getUserVoucher($userID = false, $newVoucher = 0)
    {
        $this->userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::withoutGlobalScope('voucherCountry')->where('country_id', $this->cart->country_id)->where('user_id', $this->userId)->first();

        if ($this->userVoucherAccount) {
            $this->userVoucher = $this->userVoucherAccount->voucher_balance;
            if ($this->billing) {
                if ($this->billing->voucher_used == 0) {
                    $this->userVoucher += $this->billing->voucher_used;
                }
                $this->userVoucherAccount->voucher_balance = $this->userVoucher;
                $this->userVoucherAccount->save();
            }
        } else {
            $this->userVoucherAccount = new \OlaHub\UserPortal\Models\UserVouchers;
            $this->userVoucherAccount->user_id = $userID;
            $this->userVoucherAccount->voucher_balance = 0;
            $this->userVoucherAccount->country_id = $this->cart->country_id;
            $this->userVoucherAccount->save();
            $this->userVoucher = 0;
        }
    }

    protected function setCurrencyCode()
    {
        $cart = $this->cart;
        $this->currency = \OlaHub\UserPortal\Models\Currency::whereHas('countries', function ($q) use ($cart) {
            // $q->where('id', 5);
            $q->where('id', $cart->country_id);
        })->first();
        if (!$this->currency) {
            $this->currency = app('session')->get('def_currency');
        }
    }

    protected function createUserBillingDetails()
    {
        \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $this->billing->id)->delete();
        foreach ($this->cartDetails as $this->cartItem) {
            switch ($this->cartItem->item_type) {
                case "store":
                    $billingDetails = new \OlaHub\UserPortal\Models\UserBillDetails;
                    $oneItem = $this->cartItem->itemsMainData;
                    $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($oneItem, false, false);
                    if ($itemPrice['productHasDiscount']) {
                        $price = $itemPrice['productDiscountedPrice'];
                        $originalPrice = $oneItem->price;
                    } else {
                        $price = $oneItem->price;
                        $originalPrice = $oneItem->price;
                    }
                    $billingDetails->billing_id = $this->billing->id;
                    $billingDetails->item_name = $oneItem->name;
                    $image = \OlaHub\UserPortal\Models\ItemImages::where('item_id', $oneItem->id)->first();
                    $billingDetails->item_image = $image ? $image->content_ref : NULL;
                    $details = (new \OlaHub\UserPortal\Helpers\PaymentHelper)->getBillDetails($oneItem, $price);
                    $billingDetails->item_details = serialize($details);
                    $billingDetails->item_price = $price;
                    $billingDetails->item_original_price = $originalPrice;
                    $billingDetails->from_sale = $itemPrice['productHasDiscount'] ? 1 : 0;
                    $billingDetails->quantity = $this->cartItem->quantity;
                    $billingDetails->customize_data = $this->cartItem->customize_data;
                    $billingDetails->merchant_id = $this->cartItem->merchant_id;
                    $billingDetails->store_id = $this->cartItem->store_id;
                    $billingDetails->item_id = $this->cartItem->item_id;
                    $billingDetails->from_pickup_id = isset($details['pickup']) ? $details['pickup'] : 0;
                    $billingDetails->item_type = "store";
                    $billingDetails->user_paid = $billingDetails->item_price * $billingDetails->quantity;
                    $countryCategory = isset($details['category']['catCountry']) ? $details['category']['catCountry'] : 0;
                    $billingDetails->merchant_commision_rate = isset($countryCategory['commission_percentage']) ? $countryCategory['commission_percentage'] : 0;
                    $billingDetails->merchant_commision = $billingDetails['merchant_commision_rate'] > 0 ? $price * $this->cartItem->quantity * ($countryCategory['commission_percentage'] / 100) : 0;
                    $billingDetails->save();
                    break;
                case "designer":
                    $billingDetails = new \OlaHub\UserPortal\Models\UserBillDetails;
                    $mainItem = \OlaHub\UserPortal\Models\DesginerItems::whereIn('item_ids', [$this->cartItem->item_id])->first();
                    if ($mainItem) {
                        $itemDes = false;
                        if (isset($mainItem->items) && count($mainItem->items) > 0) {
                            foreach ($mainItem->items as $oneItem) {
                                if ($oneItem["item_id"] == $this->cartItem->item_id) {
                                    $itemDes = (object) $oneItem;
                                }
                            }
                        }
                        if (!$itemDes) {
                            $itemDes = $mainItem;
                        }
                        $itemPrice = \OlaHub\UserPortal\Models\DesginerItems::checkPrice($itemDes, false, FALSE);
                        if (isset($itemPrice['productHasDiscount']) && $itemPrice['productHasDiscount']) {
                            $price = $itemPrice['productDiscountedPrice'];
                            $originalPrice = $itemDes->item_price;
                        } else {
                            $price = $itemPrice['productPrice'];
                            // $price = $itemDes->item_price;
                            $originalPrice = $itemDes->item_price;
                        }
                        $billingDetails->billing_id = $this->billing->id;
                        $billingDetails->item_name = $mainItem->item_title;
                        $image = isset($itemDes->item_image) ? $itemDes->item_image : (isset($mainItem->item_images) ? $mainItem->item_images : false);
                        $billingDetails->item_image = $image && count($image) > 0 ? $image[0] : NULL;
                        $details = (new \OlaHub\UserPortal\Helpers\PaymentHelper)->getBillDesignerDetails($itemDes, $mainItem, $price);
                        $billingDetails->item_details = serialize($details);
                        $billingDetails->item_price = $price;
                        $billingDetails->item_original_price = $originalPrice;
                        $billingDetails->from_sale = $itemPrice['productHasDiscount'] ? 1 : 0;
                        $billingDetails->quantity = $this->cartItem->quantity;
                        $billingDetails->customize_data = $this->cartItem->customize_data;
                        $billingDetails->merchant_id = $this->cartItem->merchant_id;
                        $billingDetails->store_id = $this->cartItem->store_id;
                        $billingDetails->item_id = $this->cartItem->item_id;
                        $billingDetails->item_type = "designer";
                        $billingDetails->from_pickup_id = $this->cartItem->store_id;
                        $billingDetails->user_paid = $billingDetails->item_price * $billingDetails->quantity;
                        $billingDetails->merchant_commision_rate = 0;
                        $billingDetails->merchant_commision = 0;
                        $billingDetails->save();
                    }
                    break;
            }
        }
        $this->billingDetails = $this->billing->billDetails;
    }

    protected function setRequestShipingAddress()
    {
        $return = [];
        $cityName = "";
        $regionName = "";
        if ($this->typeID != 3) {
            if (isset($this->requestData['billState'])) {
                if ($this->requestData['billState'] > 0) {
                    $country = \OlaHub\UserPortal\Models\ShippingCountries::where('olahub_country_id', $this->cart->country_id)->first();
                    $region = \OlaHub\UserPortal\Models\ShippingRegions::where('country_id', $country->id)->where('id', $this->requestData['billState'])->first();
                    $regionName = isset($region->name) ? $region->name : null;
                } else {
                    $regionName = $this->requestData['billState'];
                }
            }
            $return = [
                'full_name' => isset($this->requestData['billFullName']) ? $this->requestData['billFullName'] : null,
                'city' => "",
                'state' => $regionName,
                'phone' => isset($this->requestData['billPhoneNo']) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData['billPhoneNo']) : null,
                'address' => isset($this->requestData['billAddress']) ? $this->requestData['billAddress'] : null,
                'zipcode' => isset($this->requestData['billZipCode']) ? $this->requestData['billZipCode'] : null,
                'typeID' => isset($this->typeID) ? $this->typeID : null,
            ];
            if ($this->typeID == 2) {
                $return['for_user'] = $this->requestData['billUserID'];
            }
        }
        return $return;
    }

    protected function getCelebrationDetails()
    {

        $celebrationID = $this->celebrationID;
        $this->participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::whereHas('celebration', function ($q) use ($celebrationID) {
            $q->where('celebration_id', $celebrationID);
        })->where('user_id', app('session')->get('tempID'))->where('payment_status', '2')->first();
        if (!$this->participant) {
            throw new NotAcceptableHttpException(404);
        }
        $this->celebrationDetails = $this->participant->celebration;
        if (!$this->celebrationDetails) {
            throw new NotAcceptableHttpException(404);
        }
    }

    protected function createUserBillingHistory($paidBy = "0", $paidResult = null)
    {
        $pay_status = 0;
        if (!$this->billing) {
            $billingNum = (new \OlaHub\UserPortal\Helpers\BillsHelper)->createUserBillNumber();
            $this->billing = new \OlaHub\UserPortal\Models\UserBill;
            $this->billing->billing_number = $billingNum;
            // $this->billing->country_id = 5;
            $this->billing->country_id = $this->cart->country_id;
            $this->billing->user_id = app('session')->get('tempID');
            $this->billing->pay_for = $this->cart->celebration_id > 0 ? $this->cart->celebration_id : 0;
            $this->billing->calendar_id = $this->cart->calendar_id > 0 ? $this->cart->calendar_id : 0;
            $this->billing->billing_currency = $this->currency->code;
        } else {
            $billingNum = $this->billing->billing_number;
        }
        $billingToken = [null, null];
        if ($paidBy != "0") {
            $billingToken = (new \OlaHub\UserPortal\Helpers\SecureHelper)->creatUniquePayToken($billingNum, app('session')->get('tempID'));
            $pay_status = $this->payStatusID($paidBy, 1);
        }

        $this->billing->bill_time = $billingToken[1];
        $this->billing->bill_token = $billingToken[0];
        $this->billing->paid_by = $paidBy;
        $this->billing->is_gift = $this->typeID == 2 ? 1 : 0;
        $this->billing->gift_for = $this->cart->for_friend;
        $this->billing->gift_message = $this->typeID == 2 && isset($this->requestData['billCardGift']) ? $this->requestData['billCardGift'] : null;
        $this->billing->gift_video_ref = $this->typeID == 2 && isset($this->requestData['billCardGiftVideo']) ? str_replace(STORAGE_URL . '//', '', $this->requestData['billCardGiftVideo'])  : null;
        $this->billing->gift_date = $this->typeID == 2 && isset($this->requestData['billGiftDate']) ? $this->requestData['billGiftDate'] : null;
        $this->billing->billing_total = $this->total;
        $this->billing->billing_fees = $this->cashOnDeliver;
        // $this->billing->billing_fees = $this->shippingFees + $this->cashOnDeliver;
        $this->billing->voucher_used = $this->voucherUsed > 0 && $this->pointsUsedCurr > 0 ? ($this->voucherUsed - $this->pointsUsedCurr) : $this->voucherUsed;
        $this->billing->voucher_after_pay = $this->voucherAfterPay;
        $this->billing->points_used = $this->pointsUsedInt;
        $this->billing->points_used_curr = $this->pointsUsedCurr;
        $this->billing->temp_cart_id = $this->cart->id;
        $this->billing->billing_date = date('Y-m-d H:i:s');
        $this->billing->pay_status = $pay_status;
        $this->billing->pay_result = $paidResult;
        $this->billing->promo_code_id = $this->cart->promo_code_id;
        $this->billing->promo_code_saved = $this->promoCodeSave;
        $this->billing->order_address = serialize($this->setRequestShipingAddress());
        // $this->billing->billing_currency = 'JOD';
        $this->billing->save();
    }

    protected function payStatusID($paidBy, $cycle_order, $success = 0, $fail = 0)
    {
        $paidArray = explode("_", $paidBy);
        $return = 0;
        $paymentId = 0;
        if (isset($paidArray[1]) && $paidArray[1] > 0) {
            $paymentId = $paidArray[1];
        } elseif (isset($paidArray[0]) && $paidArray[0] > 0) {
            $paymentId = $paidArray[0];
        }

        if ($paymentId > 0) {
            $paymenStatus = \OlaHub\UserPortal\Models\PaymentShippingStatus::where("action_id", $paymentId)
                ->where("cycle_order", $cycle_order)
                ->where("is_success", $success)
                ->where("is_fail", $fail)
                ->first();
            if ($paymenStatus) {
                $return = $paymenStatus->id;
            } else {
                throw new NotAcceptableHttpException(404);
            }
        }

        return $return;
    }

    protected function updateUserVoucher()
    {
        if ($this->voucherUsed > 0) {
            if ($this->pointsUsedCurr > 0 && $this->voucherAfterPay > 0) {
                $voucherAfterPay = $this->voucherAfterPay - $this->pointsUsedCurr;
                if ($voucherAfterPay >= 0) {
                    $this->voucherAfterPay = $voucherAfterPay;
                }
            }
            $this->userVoucherAccount->voucher_balance = $this->voucherAfterPay;
            $this->userVoucherAccount->save();
            if ($this->pointsUsedInt > 0) {
                $userInsert = new \OlaHub\UserPortal\Models\UserPoints;
                $userInsert->country_id = app('session')->get('def_country')->id;
                $userInsert->campign_id = 0;
                $userInsert->points_collected = "-" . $this->pointsUsedInt;
                $userInsert->collect_date = date("Y-m-d");
                $userInsert->save();
            }
        }
    }

    protected function finalizeSuccessPayment($sendEmails = true, $cycle_order = 1, $success = 1, $fail = 0)
    {
        $pay_status = $this->payStatusID($this->billing->paid_by, $cycle_order, $success, $fail);
        $this->billing->pay_status = $pay_status;
        if (isset($this->paymentMethodCountryData->extra_fees)) {
            $this->billing->billing_fees += $this->paymentMethodCountryData->extra_fees;
        }

        $this->billing->save();
        $this->grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($this->billingDetails);
        if ($this->typeID == 1 && $sendEmails) {
            $this->finalizeSuccessMeMails();
        } elseif ($this->typeID == 2 && $sendEmails) {
            $this->finalizeSuccessGiftMails();
        } elseif ($this->typeID == 3) {
            $this->finalizeSuccessCelebrationMails();
        }

        if (!$sendEmails) {
            \OlaHub\UserPortal\Models\CartItems::where('shopping_cart_id', $this->billing->temp_cart_id)->delete();
            \OlaHub\UserPortal\Models\Cart::where('id', $this->billing->temp_cart_id)->delete();
        }

        if ($success == 1) {
            $stores = [];
            $fcmTokens = [];
            foreach ($this->billingDetails as $item) {
                if ($item->item_type != 'designer') {
                    array_push($stores, $item->store_id);
                    array_push($stores, $item->merchant_id);
                }
            }
            if (count($stores) > 0) {
                $fcms = \OlaHub\UserPortal\Models\FcmStoreToken::whereIn('for_id', $stores)->whereIn('for_type', ['merchant', 'store'])->get();
                foreach ($fcms as $fcmToken) {
                    array_push($fcmTokens, $fcmToken->fcm_token);
                }
                (new \OlaHub\UserPortal\Helpers\PaymentHelper())->sendSellersNewOrdersNotifications($fcmTokens);
            }
        }

        $this->billing->temp_cart_id = 0;
        $this->billing->save();
    }

    protected function finalizeFailPayment($reason)
    {
        $pay_status = $this->payStatusID($this->billing->paid_by, 2, 0, 1);
        $this->billing->pay_status = $pay_status;
        $this->billing->save();
        if ($this->billing->voucher_used && $this->billing->voucher_used > 0) {
            \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance(false, $this->billing->voucher_used, $this->billing->country_id);
        }
        if (app('session')->get('tempData')->email) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserFailPayment(app('session')->get('tempData'), $this->billing, $reason);
        } elseif (app('session')->get('tempData')->mobile_no) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserFailPayment(app('session')->get('tempData'), $this->billing, $reason);
        }
    }

    protected function finalizeSuccessMeMails()
    {
        \OlaHub\UserPortal\Models\CartItems::where('shopping_cart_id', $this->billing->temp_cart_id)->delete();
        \OlaHub\UserPortal\Models\Cart::where('id', $this->billing->temp_cart_id)->delete();
        if (isset($this->grouppedMers['voucher']) && $this->grouppedMers['voucher'] > 0) {
            \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance(false, $this->grouppedMers['voucher'], $this->billing->country_id);
        }
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderDirect($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderDirect($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        if (app('session')->get('tempData')->email) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserNewOrderDirect(app('session')->get('tempData'), $this->billing);
        }
        if (app('session')->get('tempData')->mobile_no) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserNewOrderDirect(app('session')->get('tempData'), $this->billing);
        }
    }

    protected function finalizeSuccessGiftMails()
    {
        \OlaHub\UserPortal\Models\CartItems::where('shopping_cart_id', $this->billing->temp_cart_id)->delete();
        \OlaHub\UserPortal\Models\Cart::where('id', $this->billing->temp_cart_id)->delete();
        $shipping = unserialize($this->billing->order_address);
        $targetID = isset($shipping['for_user']) ? $shipping['for_user'] : NULL;
        $target = UserModel::withOutGlobalScope('notTemp')->find($targetID);
        if (isset($this->grouppedMers['voucher']) && $this->grouppedMers['voucher'] > 0) {
            \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance($targetID, $this->grouppedMers['voucher'], $this->cart->country_id);
        }
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderGift($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderGift($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        if (app('session')->get('tempData')->email) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserNewOrderGift(app('session')->get('tempData'), $this->billing, $target);
        } elseif (app('session')->get('tempData')->mobile_no) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserNewOrderGift(app('session')->get('tempData'), $this->billing, $target);
        }
    }

    protected function finalizeSuccessCelebrationMails()
    {
        $this->participant->payment_status = 3;
        $this->participant->save();
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserPaymentCelebration(app('session')->get('tempData'), $this->billing);
        $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebrationDetails->id)->where('payment_status', 3)->get();
        if (count($participant) == 1) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderCelebration($this->grouppedMers, $this->billing, app('session')->get('tempData'));
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderCelebration($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        }

        $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebrationDetails->id)->get();
        $totalpats = $participants->count();
        $paidParts = 0;
        foreach ($participants as $one) {

            if ($one->payment_status == 3) {
                $paidParts += 1;
            }
        }
        if ($paidParts == $totalpats) {
            $this->celebrationDetails->celebration_status = 3;
            $this->celebrationDetails->save();
        }
    }

    protected function checkActionPermission($type)
    {
        if (!$this->userId) {
            throw new NotAcceptableHttpException(404);
        }
        if (in_array($type, ["event", "celebration"]) && !$this->id) {
            throw new UnauthorizedHttpException(401);
        }
        if ($type == "event" && $this->id > 0 && $this->userId > 0) {
            $this->userMongo = \OlaHub\UserPortal\Models\UserMongo::where("user_id", $this->userId)->first();
            $this->friends = $this->userMongo->friends;
            if (!is_array($this->friends) || count($this->friends) <= 0) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
            $time = strtotime("+3 Days");
            $minTime = date("Y-m-d", $time);
            $this->calendar = \OlaHub\UserPortal\Models\CalendarModel::whereIn("user_id", $this->friends)
                ->where("id", $this->id)
                ->where("calender_date", ">=", $minTime)
                ->first();
            if (!$this->calendar) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
        } elseif ($type == "celebration" && $this->id > 0 && $this->userId > 0) {
            $userId = $this->userId;
            $this->celebration = \OlaHub\UserPortal\Models\CelebrationModel::whereHas("celebrationParticipants", function ($q) use ($userId) {
                $q->where("user_id", $userId);
            })->where("id", $this->id)->first();
            if (!$this->celebration) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
            $this->celebrationDetails = $this->celebration;
            $celebrationID = $this->id;
            $this->participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::whereHas('celebration', function ($q) use ($celebrationID) {
                $q->where('celebration_id', $celebrationID);
            })->where('user_id', app('session')->get('tempID'))->where('payment_status', '2')->first();
            if (!$this->participant) {
                $return['status'] = false;
                $return['code'] = 404;
                $return['msg'] = "noData";
                return $return;
            }
        }
    }

    protected function cartFilter($type)
    {
        $this->cartModel = (new \OlaHub\UserPortal\Models\Cart)->newQuery();
        switch ($type) {
            case "default":
                return $this->cartModel->whereNull("calendar_id")->first();
            case "celebration":
                return $this->cartModel->withoutGlobalScope("countryUser")
                    ->whereNull("calendar_id")
                    ->whereNull("user_id")
                    ->where("celebration_id", $this->id)->first();
            case "event":
                return $this->cartModel->where("calendar_id", $this->id)->first();
        }
    }

    protected function setTypeID($type)
    {
        switch ($type) {
            case "default":
                if ($this->cart->for_friend > 0) {
                    $this->typeID = 2;
                    $this->requestData["billGiftDate"] = $this->cart->gift_date;
                    $this->requestData["billUserID"] = $this->cart->for_friend;
                } else {
                    $this->typeID = 1;
                }
                break;
            case "celebration":
                $this->typeID = $this->requestData['billType'];
                break;
            case "event":
                $this->typeID = $this->requestData['billType'];
                break;
        }
    }

    protected function checkCart($type)
    {
        if (!empty($this->requestData["isGift"])) {
            $checkCart = $this->cartFilter($type);
            $for_friend = NULL;
            $this->cartModel = \OlaHub\UserPortal\Models\Cart::getUserCart(app('session')->get('tempID'));
            $phone = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData['billPhoneNo']);
            $country_id = $checkCart->country_id;
            $userData = UserModel::where(function ($q) use ($phone, $country_id) {
                $q->where('mobile_no', $phone);
                $q->where('country_id', $country_id);
                $q->where('for_merchant', 0);
            })->first();
            if ($userData) {
                $for_friend = $userData->id;
            } else {
                $fullName = explode(" ", trim($this->requestData["billFullName"]));
                $user = new \OlaHub\UserPortal\Models\UserModel;
                // $secureHelper = new \OlaHub\UserPortal\Helpers\SecureHelper;
                $user->country_id = $country_id;
                $user->first_name = $fullName[0];
                $user->last_name = isset($fullName[1]) ? $fullName[1] : "";
                $user->last_name .= isset($fullName[2]) ? $fullName[2] : "";
                $user->mobile_no = $phone;
                $user->invited_by = app('session')->get('tempID');
                $user->is_first_login = 1;
                // $user->password = $secureHelper->setPasswordHashing(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6));
                $user->save();
                $for_friend = $user->id;
            }
            $this->cartModel->for_friend = $for_friend;
            $this->cartModel->gift_date = $this->requestData["billGiftDate"];
            $this->cartModel->save();
        }
        $this->cart = null;
        $checkCart = $this->cartFilter($type);

        if ($checkCart) {
            (new \OlaHub\UserPortal\Helpers\CartHelper)->checkOutOfStockInCartItem($checkCart->id);
            $this->cart = $checkCart;
        }

        if (!$this->cart) {
            if ($this->creatCart($type)) {
                $this->cart = $this->cartFilter($type);
            } else {
                throw new NotAcceptableHttpException(404);
            }
        }
        // print_r($this->cart); return "";
    }

    protected function creatCart($type)
    {
        $country = $this->celebration ? \OlaHub\UserPortal\Models\Country::where('id', $this->celebration->country_id)->first() : \OlaHub\UserPortal\Models\Country::where('id', app('session')->get('def_country')->id)->first();
        $this->cartModel = new \OlaHub\UserPortal\Models\Cart;
        $this->cartModel->shopping_cart_date = date('Y-m-d h:i');
        $this->cartModel->total_price = '0.00';
        $this->cartModel->currency_id = $country->currency_id;
        $this->cartModel->country_id = $country->id;
        switch ($type) {
            case "default":
                return $this->cartModel->save();
            case "celebration":
                $this->cartModel->celebration_id = $this->id;
                return $this->cartModel->save();
            case "event":
                $this->cartModel->calendar_id = $this->id;
                return $this->cartModel->save();
        }
    }
}
