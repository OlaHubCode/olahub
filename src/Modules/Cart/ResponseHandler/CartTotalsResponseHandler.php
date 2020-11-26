<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\Cart;
use League\Fractal;

class CartTotalsResponseHandler extends Fractal\TransformerAbstract
{

    private $return;
    private $data;
    private $promoCodeSave = 0;

    public function transform(Cart $data)
    {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData()
    {
        $cartSubTotal = Cart::getCartSubTotal($this->data, FALSE);
        $cartSubTotal = str_replace(",", "", $cartSubTotal);
        $this->checkPromoCode($cartSubTotal);
        $userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', app('session')->get('tempID'))->first();
        if ($userVoucherAccount) {
            $userVoucher = $userVoucherAccount->voucher_balance;
        } else {
            $userVoucher = 0;
        }
        $userPoints = 0;
        $userearnedPoints = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as pointsSum')->where('user_id', app('session')->get('tempID'))->first();
        if ($userearnedPoints) {
            $userPoints = $userearnedPoints->pointsSum;
        }
        $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
        $userReedem = $userPoints * $exchangeRate->sell_price;
        $userVoucher += $userReedem;

        $countryTo = $this->data->shipped_to ? $this->data->shipped_to : $this->data->country_id;
        $shippingFees = \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($countryTo, $this->data->country_id, $this->data);

        $cashOnDeliver = TRUE ? 0 : 3;
        $total = (float) $cartSubTotal + $shippingFees['total'] + $cashOnDeliver - $this->promoCodeSave;

        $this->return[] = ['label' => 'subtotal', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($cartSubTotal), 'className' => "subtotal"];
        $this->return[] = ['label' => 'shippingFees', 'value' => $shippingFees['shipping'], 'className' => "shippingFees"];
        if ($cashOnDeliver) {
            $this->return[] = ['label' => 'cashFees', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($cashOnDeliver)];
        }
        if ($this->promoCodeSave > 0) {
            $this->return[] = ['label' => 'getPromoSave', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($this->promoCodeSave)];
        }
        $this->return[] = ['label' => 'total', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total), 'className' => "total"];
        if ($userVoucher > 0 && $total > $userVoucher) {
            $this->return[] = ['label' => 'usedFromVoucher', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userVoucher), 'className' => "usedVoucher"];
            $this->return[] = ['label' => 'balanceToPay', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total - $userVoucher), 'className' => "balanceToPay"];
        } elseif ($userVoucher > 0 && $total < $userVoucher) {
            $this->return[] = ['label' => 'usedFromVoucher', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total), 'className' => "usedVoucher"];
            $this->return[] = ['label' => 'balanceToPay', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0), 'className' => "balanceToPay"];
        } elseif ($userVoucher > 0 && $total == $userVoucher) {
            $this->return[] = ['label' => 'usedFromVoucher', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total), 'className' => "usedVoucher"];
            $this->return[] = ['label' => 'balanceToPay', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0), 'className' => "balanceToPay"];
        } elseif ($userVoucher > 0 && $total == $userVoucher) {
            $this->return[] = ['label' => 'usedFromVoucher', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($total), 'className' => "usedVoucher"];
            $this->return[] = ['label' => 'balanceToPay', 'value' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice(0), 'className' => "balanceToPay"];
        } else {
        }
    }

    private function checkPromoCode($cartSubTotal)
    {
        if ($this->data->promo_code_id) {
            $coupon = \OlaHub\UserPortal\Models\Coupon::find($this->data->promo_code_id);
            if ($coupon) {
                $checkValid = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponValid($coupon);
                if ($checkValid == "valid") {
                    if ($coupon->code_for == "cart") {
                        $this->promoCodeSave = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponCart($coupon, $cartSubTotal, $this->data);
                    }
                }
            }
        }
    }
}
