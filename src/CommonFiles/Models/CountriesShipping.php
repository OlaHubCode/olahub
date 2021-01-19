<?php

/**
 * Countries model 
 * To connect with database and make all queries  
 * all functions return with eloqouent object or array of objects
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0 
 */

namespace OlaHub\UserPortal\Models;

class CountriesShipping extends \Illuminate\Database\Eloquent\Model
{

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
    }

    protected $table = 'countries_shipping_fees';
    protected $guarded = array('created_at', 'updated_at', 'deleted_at', 'id', 'name', 'two_letter_iso_code', 'three_letter_iso_code', 'language_id', 'currency_id', 'is_published', 'is_supported');

    protected static function boot()
    {
        parent::boot();
    }

    static function getShippingFees($countryID, $defaultCountry = NULL, $cart = NULL, $celebration = NULL, $cityID = null)
    {

        $promoSave = CountriesShipping::checkPromoCode($cart);

        $cityIsMust = false;
        $shoppingItems = $cart->cartDetails()->get();
        $chkItems = \OlaHub\UserPortal\Models\CartItems::with('itemsData')->where('shopping_cart_id', $cart->id)->get()->toArray();
        $checkVoucherItems = \OlaHub\UserPortal\Models\CartItems::checkIfItemsNotVoucher($chkItems);

        if ($checkVoucherItems || $promoSave) {
            return array(
                'total' => 0,
                'shipping' => array(['country' => NULL, 'amount' => "0.00 " . $transCur]),
                'saving' => NULL
            );
        } else {
            if (!$defaultCountry)
                $defaultCountry = $countryID;
            $shippingFees = [];
            $shippingSavings = [];
            $shippingFeesTotal = 0;

            foreach ($shoppingItems as $item) {
                if ($item->item_type == 'designer')
                    $brand = \OlaHub\UserPortal\Models\Designer::find($item->merchant_id);
                else
                    $brand = \OlaHub\UserPortal\Models\Merchant::withoutGlobalScope('country')->find($item->merchant_id);
                $country = \OlaHub\UserPortal\Models\Country::withoutGlobalScope('countrySupported')->find($brand->country_id);
                $currency = $country->currencyData;
                $transCur = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency);

                $shipping = CountriesShipping::join('countries', 'countries_shipping_fees.country_from', 'countries.id')
                    ->where("country_from", $brand->country_id)
                    ->where("country_to", $countryID)
                    ->where("is_shipping", 1)
                    ->first();
                if (!$shipping) {
                    $shipping = CountriesShipping::join('countries', 'countries_shipping_fees.country_from', 'countries.id')
                        ->where("country_from", $brand->country_id)
                        ->where("country_to", 0)
                        ->where("is_shipping", 1)
                        ->first();
                }
                $countryName = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($shipping, 'name');

                if ($brand->country_id == $countryID && $shipping && $brand->country_id != 0) {

                    $cityShipping = ShippingCities::where("country_id", $countryID)->where("id", $cityID)->first();
                    $shipping = @$cityShipping->total_shipping > 0 ? $cityShipping : $shipping;
                    $cityIsMust = true;
                    $cityName = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($cityShipping, 'name');
                }

                $key = array_search($countryName, array_column($shippingFees, 'country'));
                if ($key == false && gettype($key) == 'boolean') {
                    $amount = CurrnciesExchange::getCurrncy("USD", $currency->code, @$shipping->total_shipping);

                    if ($celebration) {
                        $participant = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebration->id)
                            ->where('user_id', app('session')->get('tempID'))->first();
                        $pp = $celebration->celebrationParticipants->count();
                        $debt = $amount / $pp;
                        $debt = $debt - fmod($debt, MOD_CELEBRATION);
                        if ($participant->is_creator)
                            $amount = ($amount == ($debt * $pp) ? $debt : ($amount - ($debt * $pp)) + $debt);
                        else
                            $amount = $debt;
                    }
                    $needCity = "depend on shipping address";
                    if ((app('session')->get('def_lang')->default_locale == 'ar'))
                        $needCity = "سسسسسسسسسسسسسس";
                    $shippingFees[] = array('country' => $countryName, 'amount' => ($cityIsMust && !isset($cityName)) ? $needCity : number_format($amount, 2) . " " . $transCur, 'city' => isset($cityName) ? $cityName : "");
                    if (($cityIsMust && $cityName) || !$cityIsMust)
                        $shippingFeesTotal += $amount;

                    $shippingSavings[] = array(
                        'amount' => number_format($amount, 2),
                        'currency' => $currency->toArray(),
                        'country' => json_decode(@$shipping->name)

                    );
                }
            }
            return array(
                'total' => ($cityIsMust && !$cityName) ? "needCity" : $shippingFeesTotal,
                'shipping' => $shippingFees,
                'saving' => $shippingSavings
            );
        }
    }

    static function checkPromoCode($cart)
    {
        if ($cart->promo_code_id) {
            $coupon = \OlaHub\UserPortal\Models\Coupon::find($cart->promo_code_id);
            if ($coupon) {
                $checkValid = (new \OlaHub\UserPortal\Helpers\CouponsHelper)->checkCouponValid($coupon);
                if ($checkValid == "valid") {
                    if ($coupon->code_for == "cart" && $coupon->unique_code == 'June2020') {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
