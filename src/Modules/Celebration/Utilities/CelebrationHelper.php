<?php

namespace OlaHub\UserPortal\Helpers;

class CelebrationHelper extends OlaHubCommonHelper
{

    function saveCelebrationCart($celebration, $requestData = false)
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Save celebration in cart", "action_startData" => $celebration . $requestData]);
        $cart = (new \OlaHub\UserPortal\Helpers\CartHelper)->setCelebrationCartData($celebration, $requestData);
        $totalPrice = \OlaHub\UserPortal\Models\Cart::getCartSubTotal($cart, false);
        
        if ($totalPrice >= 0) {
            $celebrationCart = \OlaHub\UserPortal\Models\CelebrationModel::where('id', $cart->celebration_id)->first();
            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebrationCart->id)->get();

            $ifShiping = $cart->cartDetails()->whereHas('itemsMainData', function ($q) {
                $q->where('is_shipment_free', '1');
            })->first();
            $shippingFees = $ifShiping ? \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($cart->country_id) : 0;
            $shippingFees += \OlaHub\UserPortal\Models\Cart::checkDesignersShipping($cart, $shippingFees);

            $pp = count($participants);
            $totalPrice = $totalPrice + $shippingFees;
            $price = ($totalPrice / $pp);
            $price = number_format($price - fmod($price, MOD_CELEBRATION), 2, ".", "");
            $creatorAmount = ($totalPrice == ($price * $pp) ? $price : number_format(($totalPrice - ($price * $pp)) + $price, 2, ".", ""));

            $celebrationCart->participant_count = count($participants);
            $celebrationCart->save();
            foreach ($participants as $participant) {
                if ($participant->is_creator) {
                    $participant->amount_to_pay = $creatorAmount;
                } else {
                    $participant->amount_to_pay = $price;
                }
                $participant->save();
            }
        }
    }
}
