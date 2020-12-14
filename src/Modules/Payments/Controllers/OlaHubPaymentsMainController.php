<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\UserPortal\Models\PaymentMethod;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use OlaHub\UserPortal\Models\UserModel;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

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
    protected $promoCodeName;
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
            $chkItems = \OlaHub\UserPortal\Models\CartItems::with('itemsData')->where('shopping_cart_id', $this->cart->id)->get()->toArray();
            $checkVoucherItems = \OlaHub\UserPortal\Models\CartItems::checkIfItemsHasVoucher($chkItems);
            $this->getPaymentMethodsDetails($this->cart->country_id, $this->cart->shipped_to, $checkVoucherItems);
            $this->return = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($this->paymentMethodCountryData, '\OlaHub\UserPortal\ResponseHandlers\PaymentResponseHandler');
        }
        $this->return['status'] = true;
        $this->return['code'] = 200;
        if (($this->typeID == '1' || !($this->cart->for_friend > 0)) && !$this->cart->shipped_to) {
            $this->return['shippingAddress'] = \OlaHub\UserPortal\Models\UserShippingAddressModel::checkUserShippingAddress(app('session')->get('tempID'), $this->cart->country_id);
        } elseif ($this->typeID == '2' && $this->cart->for_friend > 0) {
            $this->return['shippingAddress'] = \OlaHub\UserPortal\Models\UserShippingAddressModel::checkUserShippingAddress($this->cart->for_friend, $this->cart->country_id);
        } else {
            $this->return['shippingAddress'] = [];
        }
        return response($this->return, 200);
    }

    function checkPayPoint($save = false)
    {
        $points = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as total_points')->first();
        $this->pointsUsedInt = $points->total_points;
        if ($this->billing && $this->billing->points_used > 0) {
            $this->pointsUsedInt += $this->billing->points_used;
            if ($save) {
                $userInsert = new \OlaHub\UserPortal\Models\UserPoints;
                $userInsert->country_id = app('session')->get('def_country')->id;
                $userInsert->campign_id = 0;
                $userInsert->points_collected = $this->billing->points_used;
                $userInsert->collect_date = date("Y-m-d");
                $userInsert->save();
            }
            $this->billing->points_used = 0;
            $this->billing->save();
        }
        if ($this->pointsUsedInt > 0) {
            $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
            $this->pointsUsedCurr = $this->pointsUsedInt * $exchangeRate->sell_price;
            $this->userVoucher += $this->pointsUsedCurr;
        }
    }

    protected function checkCrossCountries()
    {
        $getIPInfo = new \OlaHub\UserPortal\Helpers\getIPInfo();
        $countryID = $this->cart->country_id;
    }

    protected function getCartDetails()
    {
        $this->cartDetails = $this->cart->cartDetails()->get();
        if (!($this->cartDetails->count() > 0)) {
            throw new NotAcceptableHttpException(404);
        }
    }

    protected function checkPromoCode($cartSubTotal, $recordUse = false)
    {
        $promoID = $this->cart ? $this->cart->promo_code_id : $this->billing->promo_code_id;
        if ($promoID) {
            $coupon = \OlaHub\UserPortal\Models\Coupon::find($promoID);

            if ($coupon) {
                $checkValid = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponValid($coupon);
                if ($checkValid == "valid") {
                    if ($coupon->code_for == "cart") {
                        $this->promoCodeName = $coupon->unique_code;
                        $this->promoCodeSave = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponCart($coupon, $cartSubTotal, $this->cart, $recordUse);
                    }
                }
            }
        }
    }

    protected function setCartTotal($withExtra = true)
    {
        $this->cartTotal = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($this->cart, false);
        $this->cartTotal = str_replace(",", "", $this->cartTotal);
        $this->checkPromoCode($this->cartTotal);
        if ($this->promoCodeName == 'June2020') {
            $this->shippingFees = 0;
        } else {
            $this->shippingFees = $this->cart->shipment_fees;
            // if ($withExtra) {
            //     if ($this->paymentMethodCountryData) {
            //         $this->cashOnDeliver = $this->paymentMethodCountryData->extra_fees;
            //     }
            // }
        }
        $this->total = (float) $this->cartTotal + $this->shippingFees + $this->cashOnDeliver - $this->promoCodeSave;

        if ($this->celebration) {
            if ($this->promoCodeName == 'June2020') {
                $this->shippingFees = 0;
            } else {
                $shippingFees = \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($this->cart->country_id, $this->cart->country_id, $this->cart, $this->celebration);
                $this->shippingFees = $shippingFees['total'];
            }

            $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebration->id)
                ->where('user_id', app('session')->get('tempID'))->first();
            $this->total = $participant->amount_to_pay;
        }
    }

    protected function getPaymentMethodsDetails($country, $shipped_to = NULL, $ifHasVoucher = NULL)
    {
        $typeID = $this->typeID;
        if ($this->crossCountry) {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::where('country_id', $country)
                ->whereHas('PaymentData', function ($q) use ($typeID, $shipped_to, $ifHasVoucher) {
                    $q->whereHas('typeDataSync', function ($query) use ($typeID) {
                        $query->where('lkp_payment_method_types.id', $typeID);
                    });
                    if ($shipped_to || $ifHasVoucher)
                        $q->where('prepare_func', '<>', 'cashOnDeliverySystem');
                })
                ->where('is_cross', '1')
                ->get();
        } else {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::where('country_id', $country)
                ->whereHas('PaymentData', function ($q) use ($typeID, $ifHasVoucher) {
                    $q->whereHas('typeDataSync', function ($query) use ($typeID, $ifHasVoucher) {
                        $query->where('lkp_payment_method_types.id', $typeID);
                        if ($ifHasVoucher)
                            $query->where('prepare_func', '<>', 'cashOnDeliverySystem');
                    });
                })->groupBy("payment_method_id")->where('is_published', 1)->get();
        }

        if (!$this->paymentMethodCountryData->count()) {
            $this->paymentMethodCountryData = \OlaHub\UserPortal\Models\ManyToMany\PaymentCountryRelation::whereHas('PaymentData', function ($q) use ($typeID) {
                $q->where("accept_cross", "1");
                $q->whereHas('typeDataSync', function ($query) use ($typeID) {
                    $query->where('lkp_payment_method_types.id', $typeID);
                });
            })->groupBy('payment_method_id')->get();
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
        \OlaHub\UserPortal\Models\UserBill::where('temp_cart_id', $this->cart->id)->where('user_id', app('session')->get('tempID'))->delete();
    }

    protected function getUserVoucher($userID = false, $newVoucher = 0)
    {
        $countryID = $this->cart ? $this->cart->country_id : $this->billing->country_id;
        $userId = $this->userId ? $this->userId : $this->billing->user_id;
        $this->total = $this->total ? $this->total : @$this->billing->billing_total;
        $this->userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::withoutGlobalScope('voucherCountry')->where('country_id', $countryID)->where('user_id', $userId)->first();

        if ($this->userVoucherAccount) {
            $this->userVoucher = $this->userVoucherAccount->voucher_balance;
            if ($this->userVoucher > 0 && $this->total > $this->userVoucher) {
                $this->voucherUsed = $this->userVoucher;
                $this->voucherAfterPay = 0;
            }
            if ($this->userVoucher > 0 && $this->total <= $this->userVoucher) {
                $this->voucherUsed = $this->total;
                $this->voucherAfterPay = $this->userVoucher - $this->total;
            }
        } else {
            $this->userVoucherAccount = new \OlaHub\UserPortal\Models\UserVouchers;
            $this->userVoucherAccount->user_id = $userID;
            $this->userVoucherAccount->voucher_balance = 0;
            $this->userVoucherAccount->country_id = $countryID;
            $this->userVoucherAccount->save();
            $this->userVoucher = 0;
        }
    }

    protected function setCurrencyCode()
    {
        $cart = $this->cart;
        $this->currency = \OlaHub\UserPortal\Models\Currency::whereHas('countries', function ($q) use ($cart) {
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
                    $oneItem = $this->cartItem->itemsMainData;
                    $billingDetails = new \OlaHub\UserPortal\Models\UserBillDetails;
                    $itemPrice = \OlaHub\UserPortal\Models\CatalogItem::checkPrice($oneItem, false, false);
                    if ($itemPrice['productHasDiscount']) {
                        $price = $itemPrice['productDiscountedPrice'];
                        $originalPrice = $oneItem->price;
                        $exchangedPrice = $itemPrice['productDiscountedPrice'];
                    } else {
                        $price = $oneItem->price;
                        $originalPrice = $oneItem->price;
                        $exchangedPrice = $oneItem->price;
                    }

                    $exchangedPrice = str_replace(",", "", $exchangedPrice);
                    $billingDetails->billing_id = $this->billing->id;
                    $billingDetails->item_name = $oneItem->name;
                    $image = \OlaHub\UserPortal\Models\ItemImages::where('item_id', $oneItem->id)->first();
                    $billingDetails->item_image = $image ? $image->content_ref : NULL;
                    $details = (new \OlaHub\UserPortal\Helpers\PaymentHelper)->getBillDetails($oneItem, $price);
                    $billingDetails->item_details = serialize($details);
                    $billingDetails->item_price = $price;
                    $billingDetails->item_original_price = $originalPrice;
                    $billingDetails->country_paid = $exchangedPrice;
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
                    $oneItem = $this->cartItem->itemsDesignerData;
                    $billingDetails = new \OlaHub\UserPortal\Models\UserBillDetails;
                    $itemPrice = \OlaHub\UserPortal\Models\DesignerItems::checkPrice($oneItem, false, FALSE);
                    if (isset($itemPrice['productHasDiscount']) && $itemPrice['productHasDiscount']) {
                        $price = $itemPrice['productDiscountedPrice'];
                        $originalPrice = $oneItem->price;
                    } else {
                        $price = $oneItem->price;
                        $originalPrice = $oneItem->price;
                    }
                    $exchangedPrice = $itemPrice['productPrice'];
                    $exchangedPrice = str_replace(",", "", $exchangedPrice);

                    $billingDetails->billing_id = $this->billing->id;
                    $billingDetails->item_name = $oneItem->name;
                    $image = \OlaHub\UserPortal\Models\DesignerItemImages::where('item_id', $oneItem->id)->first();
                    $billingDetails->item_image = $image ? $image->content_ref : NULL;
                    $details = (new \OlaHub\UserPortal\Helpers\PaymentHelper)->getBillDesignerDetails($oneItem, $price);
                    $billingDetails->item_details = serialize($details);
                    $billingDetails->item_price = $price;
                    $billingDetails->item_original_price = $originalPrice;
                    $billingDetails->country_paid = $exchangedPrice;
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
                    break;
            }
        }
        $this->billingDetails = $this->billing->billDetails;
    }

    protected function setRequestShipingAddress()
    {
        $return = [];
        $countryID = $this->cart->shipped_to ? $this->cart->shipped_to : $this->cart->country_id;
        $country = \OlaHub\UserPortal\Models\ShippingCountries::where('olahub_country_id', $countryID)->first();
        if ($this->typeID < 3) {
            $return = [
                'country' => $country->name,
                'full_name' => isset($this->requestData['billFullName']) ? $this->requestData['billFullName'] : null,
                'city' => isset($this->requestData['billCity']) ? $this->requestData['billCity'] : null,
                'phone' => isset($this->requestData['billPhoneNo']) ? (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData['billPhoneNo']) : null,
                'address' => isset($this->requestData['billAddress']) ? $this->requestData['billAddress'] : null,
                'zipcode' => isset($this->requestData['billZipCode']) ? $this->requestData['billZipCode'] : null,
                'typeID' => isset($this->typeID) ? $this->typeID : null,
                'location' =>  isset($this->requestData['billLocation']) ? json_encode($this->requestData['billLocation']) : null
            ];
            if ($this->typeID == 2) {
                $return['for_user'] = $this->requestData['billUserID'];
            }
        }
        if ($this->typeID == 3) {
            $celebrationAddress = \OlaHub\UserPortal\Models\CelebrationShippingAddressModel::where('celebration_id', $this->cart->celebration_id)->first();
            $return = [
                'country' => $country->name,
                'full_name' => $celebrationAddress->shipping_address_full_name,
                'city' => $celebrationAddress->shipping_address_city,
                'phone' => $celebrationAddress->shipping_address_phone_no,
                'address' => $celebrationAddress->shipping_address_address_line1,
                'zipcode' => $celebrationAddress->shipping_address_zip_code,
                'typeID' => isset($this->typeID) ? $this->typeID : null,
                'location' => NULL
            ];
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
            $this->billing->country_id =  app('session')->get('def_country')->id;
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
        $this->billing->shipping_fees = $this->shippingFees;
        $this->billing->shipment_details = $this->cart->shipment_details;
        $this->billing->billing_fees = $this->cashOnDeliver;
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
        $this->getUserVoucher();
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
        // if (isset($this->paymentMethodCountryData->extra_fees)) {
        //     $this->billing->billing_fees += $this->paymentMethodCountryData->extra_fees;
        // }
        $this->billing->save();
        $subTotal = (float) $this->billing->billing_total -  $this->billing->shipping_fees +  $this->billing->promo_code_saved;
        $this->checkPromoCode($subTotal, true);
        $this->checkPayPoint(true);
        $this->updateUserVoucher();
        if ($this->typeID == 1 && $sendEmails) {
            $this->finalizeSuccessMeMails();
        } elseif ($this->typeID == 2 && $sendEmails) {
            $this->finalizeSuccessGiftMails();
        } elseif ($this->typeID == 3) {
            $this->finalizeSuccessCelebrationMails();
        }
        if (PRODUCTION_LEVEL)
            $googleAnalytics = $this->googleAnalytics();

        if (!$sendEmails) {
            $this->grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($this->billingDetails);
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
        if (!empty(app('session')->get('tempData')->email)) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserFailPayment(app('session')->get('tempData'), $this->billing, $reason);
        }
        if (!empty(app('session')->get('tempData')->mobile_no)) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserFailPayment(app('session')->get('tempData'), $this->billing, $reason);
        }
    }

    protected function finalizeSuccessMeMails()
    {
        $this->grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($this->billingDetails);
        \OlaHub\UserPortal\Models\CartItems::where('shopping_cart_id', $this->billing->temp_cart_id)->delete();
        \OlaHub\UserPortal\Models\Cart::where('id', $this->billing->temp_cart_id)->delete();
        if (isset($this->grouppedMers['voucher']) && $this->grouppedMers['voucher'] > 0) {
            \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance(false, $this->grouppedMers['voucher'], $this->billing->country_id);
        }
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderDirect($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderDirect($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        if (!empty(app('session')->get('tempData')->email)) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserNewOrderDirect(app('session')->get('tempData'), $this->billing, $this->grouppedMers);
        }
        if (!empty(app('session')->get('tempData')->mobile_no)) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserNewOrderDirect(app('session')->get('tempData'), $this->billing);
        }
    }

    protected function finalizeSuccessGiftMails()
    {
        $this->grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($this->billingDetails);
        \OlaHub\UserPortal\Models\CartItems::where('shopping_cart_id', $this->billing->temp_cart_id)->delete();
        \OlaHub\UserPortal\Models\Cart::where('id', $this->billing->temp_cart_id)->delete();
        $shipping = unserialize($this->billing->order_address);
        $targetID = isset($shipping['for_user']) ? $shipping['for_user'] : NULL;
        $target = UserModel::withOutGlobalScope('notTemp')->find($targetID);
        if (isset($this->grouppedMers['voucher']) && $this->grouppedMers['voucher'] > 0) {
            \OlaHub\UserPortal\Models\UserVouchers::updateVoucherBalance($targetID, $this->grouppedMers['voucher'], $this->billing->country_id);
        }
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderGift($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderGift($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        if (!empty(app('session')->get('tempData')->email)) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserNewOrderGift(app('session')->get('tempData'), $this->billing, $this->grouppedMers);
        }
        if (!empty(app('session')->get('tempData')->mobile_no)) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserNewOrderGift(app('session')->get('tempData'), $this->billing);
        }
    }

    protected function finalizeSuccessCelebrationMails()
    {
        $this->participant->payment_status = 3;
        $this->participant->save();
        if (!empty(app('session')->get('tempData')->email)) {
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendUserPaymentCelebration(app('session')->get('tempData'), $this->billing);
        }
        if (!empty(app('session')->get('tempData')->mobile_no)) {
            (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendUserNewOrderDirect(app('session')->get('tempData'), $this->billing);
        }
        $participantPaids = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebrationDetails->id)->where('payment_status', 3)->get()->count();
        if ($participantPaids == 1) {
            $this->grouppedMers = \OlaHub\UserPortal\Helpers\PaymentHelper::groupBillMerchants($this->billingDetails);
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendSalesNewOrderCelebration($this->grouppedMers, $this->billing, app('session')->get('tempData'));
            (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendMerchantNewOrderCelebration($this->grouppedMers, $this->billing, app('session')->get('tempData'));
        }

        $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $this->celebrationDetails->id)->get();
        $participantsCount = count($participants);
        if ($participantPaids == $participantsCount) {
            $this->celebrationDetails->celebration_status = 3;
            $this->celebrationDetails->save();
            if ($this->celebrationDetails->registry_id) {
                foreach ($this->billingDetails as $item) {
                    \OlaHub\UserPortal\Models\RegistryGiftModel::where('registry_id', $this->celebrationDetails->registry_id)
                        ->where('item_id', $item->item_id)->where('item_type', $item->item_type)->update(['status' => 3]);
                }
                $allItems = \OlaHub\UserPortal\Models\RegistryGiftModel::where('registry_id', $this->celebrationDetails->registry_id)->get();
                $boughtItem = true;
                foreach ($allItems as $item) {
                    if ($item->status != 3)
                        $boughtItem = false;
                }
                if ($boughtItem)
                    \OlaHub\UserPortal\Models\RegistryModel::where('id', $this->celebrationDetails->registry_id)->update(['status' => 3]);
            }
        }
        $userData = app('session')->get('tempData');
        foreach ($participants as $participant) {
            if ($participant->user_id != $userData->id) {
                $participantData = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                \OlaHub\UserPortal\Models\Notifications::sendFCM(
                    $participantData->id,
                    "payment_celebration",
                    array(
                        "type" => "payment_celebration",
                        "celebrationId" => $this->celebrationDetails->id,
                        "celebrationTitle" => $this->celebrationDetails->title,
                        "username" => "$userData->first_name $userData->last_name",
                    ),
                    $participantData->lang,
                    "$userData->first_name $userData->last_name",
                    $this->celebrationDetails->title
                );
            }
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
                $user->country_id = $country_id;
                $user->first_name = $fullName[0];
                $user->last_name = isset($fullName[1]) ? $fullName[1] : "";
                $user->last_name .= isset($fullName[2]) ? $fullName[2] : "";
                $user->mobile_no = $phone;
                $user->invited_by = app('session')->get('tempID');
                $user->is_first_login = 1;
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
            (new \OlaHub\UserPortal\Helpers\CartHelper)->checkOutOfStockInCartItem($checkCart->id, $this->celebration);
            $this->cart = $checkCart;
        }

        if (!$this->cart) {
            if ($this->creatCart($type)) {
                $this->cart = $this->cartFilter($type);
            } else {
                throw new NotAcceptableHttpException(404);
            }
        }
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

    protected function googleAnalytics()
    {

        $analytics = new Analytics(true);
        $analytics->setProtocolVersion('1')
            ->setTrackingId('UA-88242677-1')
            ->setClientId($this->billing->user_id);


        $analytics->setTransactionId($this->billing->billing_number) // transaction id. required
            ->setRevenue($this->billing->billing_total)
            ->setShipping($this->billing->shipping_fees)
            ->setTax("0")
            ->sendTransaction();


        foreach ($this->billingDetails as $item) {

            $response = $analytics->setTransactionId($this->billing->billing_number) // transaction id. required, same value as above
                ->setItemName($item->item_name) // required
                ->setItemCode($item->item_id) // SKU or id
                ->setItemCategory($item->item_type)
                ->setItemPrice($item->item_price)
                ->setItemQuantity($item->quantity)
                ->setCurrencyCode($this->billing->billing_currency)
                ->sendItem();
        }

        return true;
    }
}
