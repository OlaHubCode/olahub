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
            $price = number_format($totalPrice / count($participants), 2, ".", "");
            $remainder = ($totalPrice == ($price * count($participants)) ? 0 : number_format($totalPrice - ($price * count($participants)), 2, ".", ""));
            $celebrationCart->participant_count = count($participants);
            $celebrationCart->save();
            foreach ($participants as $participant) {
                if ($participant->is_creator) {
                    $participant->amount_to_pay = $price + $remainder;
                } else {
                    $participant->amount_to_pay = $price;
                }
                $participant->save();
            }
        }
    }
}
