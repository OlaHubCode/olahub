<?php

namespace OlaHub\UserPortal\Models;

use Illuminate\Database\Eloquent\Model;

class UserVouchers extends Model {

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('voucherCountry', function ($query) {
            $query->where('country_id', app('session')->get('def_country')->id);
        });
    }

    protected $table = 'user_voucher_balance';
    static $columnsMaping = [];

    public function country() {
        return $this->belongsTo('OlaHub\UserPortal\Models\Country', 'country_id');
    }

    public function user() {
        return $this->belongsTo('OlaHub\UserPortal\Models\UserModel', 'user_id');
    }

    static function updateVoucherBalance($userID = false, $newVoucher = 0, $countryID = false, $returnBalance = false) {
        if (!$countryID) {
            $countryID = app('session')->get('def_country')->id;
        }
        if (!$userID) {
            $userID = app('session')->get('tempID');
            $userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::withoutGlobalScope('voucherCountry')->where('country_id', $countryID)->where('user_id', $userID)->first();
        } else {
            $userVoucherAccount = \OlaHub\UserPortal\Models\UserVouchers::withoutGlobalScope('voucherCountry')->where('country_id', $countryID)->where('user_id', $userID)->first();
        }
        
        if ($userVoucherAccount) {
            $userVoucherAccount->voucher_balance += $newVoucher;
        } else {
            $userVoucherAccount = new \OlaHub\UserPortal\Models\UserVouchers;
            $userVoucherAccount->user_id = $userID;
            $userVoucherAccount->voucher_balance = $newVoucher;
            $userVoucherAccount->country_id = $countryID;
        }
        $userVoucherAccount->save();
        
        if($returnBalance){
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userVoucherAccount->voucher_balance);
        }
    }
    
    static function getUserBalance() {
        $purshasedVoucher = 0;
        $voucher = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', app('session')->get('tempData')->id)->first();
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
        return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($totalBalance);
    }

}
