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

            $shippingFees = \OlaHub\UserPortal\Models\CountriesShipping::getShippingFees($cart->country_id, $cart->country_id, $cart);

            $pp = count($participants);
            $totalPrice = number_format($totalPrice + $shippingFees['total'], 2);
            $price = ($totalPrice / $pp);
            $price = number_format($price - fmod($price, MOD_CELEBRATION), 2);
            $creatorAmount = ($totalPrice == ($price * $pp) ? $price : number_format(($totalPrice - ($price * $pp)) + $price, 2));

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
