<?php

namespace OlaHub\UserPortal\Helpers;

class UserShippingAddressHelper extends OlaHubCommonHelper {

    function getUserShippingAddress($userData, $requestData = []) {
        $userShippingAddress = $userData->shippingAddress;
        if (!$userShippingAddress) {
            $userShippingAddress = new \OlaHub\UserPortal\Models\UserShippingAddressModel;
        }
        foreach ($requestData as $input => $value) {
            if(isset(\OlaHub\UserPortal\Models\UserShippingAddressModel::$columnsMaping[$input])){
                $userShippingAddress->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(\OlaHub\UserPortal\Models\UserShippingAddressModel::$columnsMaping, $input)} = $value;
            }
        }
        $userShippingAddress->save();
        return $userShippingAddress;
    }

}
