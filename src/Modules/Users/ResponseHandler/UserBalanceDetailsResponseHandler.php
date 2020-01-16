<?php

namespace OlaHub\UserPortal\ResponseHandlers;

use OlaHub\UserPortal\Models\UserModel;
use League\Fractal;

class UserBalanceDetailsResponseHandler extends Fractal\TransformerAbstract {

    private $return;
    private $data;

    public function transform(UserModel $data) {
        $this->data = $data;
        $this->setDefaultData();
        return $this->return;
    }

    private function setDefaultData() {
        $purshasedVoucher = 0;
        $voucher = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', $this->data->id)->first();
        if ($voucher) {
            $purshasedVoucher = $voucher->voucher_balance;
        }
        $totalPointsNumber = 0;
        $totalPointsCurrency = 0;
        $pointPrice = 0;
        $points = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as points')->where('user_id', app('session')->get('tempID'))->first();
        $exchangeRate = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
        if ($exchangeRate) {
            $pointPrice = $exchangeRate->sell_price;
        }
        if ($points->points > 0) {
            $totalPointsNumber = $points->points;
            $totalPointsCurrency = $totalPointsNumber * $pointPrice;
        }
        $totalBalance = $purshasedVoucher + $totalPointsCurrency;
        $this->return = [
            "purshasedVoucher" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($purshasedVoucher),
            "totalPointsNumber" => $totalPointsNumber,
            "pointPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($pointPrice),
            "totalBalance" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($totalBalance),
        ];
    }

}
