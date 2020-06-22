<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use OlaHub\Models\AdsMongo;

class AdsController extends BaseController
{

    private $requestData;
    private $requestFilter;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = $return['requestData'];
        $this->requestFilter = $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
    }

    /**
     * Get all stores by filters and pagination
     *
     * @param  Request  $request constant of Illuminate\Http\Request
     * @return Response
     */
    public function homePageAds()
    {
        /* homepage */
        $mainAds = \OlaHub\Models\AdSlotsCountries::whereHas('mainSlotDetails', function ($q) {
            $q->where('parent_id', '1');
        })->orderBy('slot_order', 'ASC')->get();
        $adsReturn = [];
        foreach ($mainAds as $one) {
            $ad = AdsMongo::where('country', app('session')->get('def_country')->id)->where('slot', $one->id)->first();
            if ($ad) {
                AdsMongo::setAdView($ad);
                $adsReturn[] = [
                    "adToken" => isset($ad->token) ? $ad->token : NULL,
                    "adSlot" => isset($ad->slot) ? $ad->slot : 0,
                    "adRef" => isset($ad->content_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($ad->content_ref) : NULL,
                    "adText" => isset($ad->content_text) ? $ad->content_text : NULL,
                    "adLink" => isset($ad->access_link) ? $ad->access_link : NULL,
                    "adTarget" => "blank",
                ];
            } else {
                $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($one, "default_image");
                $adsReturn[] = [
                    "adToken" => NULL,
                    "adSlot" => $one->id,
                    "adRef" => isset($one->default_image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image) : NULL,
                    "adText" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($one, "default_text"),
                    "adLink" => isset($one->default_url) ? $one->default_url : NULL,
                    "adTarget" => "self",
                ];
            }
        }
        $return['homepage'] = $adsReturn;
        $return['status'] = TRUE;
        $return['code'] = 200;
        /* end homepage */

        /* holders */
        if (app("session")->get("tempID")) {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("placeholders", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [2, 3])
                ->get();
        } else {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("placeholders", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [1, 3])
                ->get();
        }
        $adsReturn = [];
        foreach ($mainAds as $ad) {
            $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_ref");
            $adsReturn[] = [
                "holderRef" => $image ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(null),
                "holderText" => isset($ad->content_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_text") : NULL,
                "holderLink" => isset($ad->content_link) ? $ad->content_link : NULL,
            ];
        }
        $return['holders'] = $adsReturn;
        /* end holders */

        /* slider */
        if (app("session")->get("tempID")) {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [2, 3])
                ->get();
        } else {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [1, 3])
                ->get();
        }
        $adsReturn = [];
        foreach ($mainAds as $ad) {
            $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_ref");
            if (\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkContentUrl($image)) {
                $adsReturn[] = [
                    "sliderRef" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image),
                    "sliderText" => isset($ad->content_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_text") : NULL,
                    "sliderLink" => isset($ad->content_link) ? $ad->content_link : NULL,
                ];
            }
        }
        $return['slider'] = $adsReturn;
        /* end slider */
        return response($return, 200);
    }

    public function homePageSlider()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Announcement", 'function_name' => "Home page slider"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetching home page slider"]);
        if (app("session")->get("tempID")) {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [2, 3])
                ->get();
        } else {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [1, 3])
                ->get();
        }
        $adsReturn = [];
        foreach ($mainAds as $ad) {
            $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_ref");
            if (\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkContentUrl($image)) {
                $adsReturn[] = [
                    "sliderRef" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image),
                    "sliderText" => isset($ad->content_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_text") : NULL,
                    "sliderLink" => isset($ad->content_link) ? $ad->content_link : NULL,
                ];
            }
        }
        $return['data'] = $adsReturn;
        if (count($adsReturn) > 0) {
            $return['status'] = TRUE;
            $return['code'] = 200;
        } else {
            $return['status'] = FALSE;
            $return['code'] = 204;
            $return['msg'] = 'NoData';
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch home page slider"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response($return, 200);
    }

    public function homePagePlacehoders()
    {


        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Announcement", 'function_name' => "Home Page Placehoders"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetching home page placeholders"]);
        if (app("session")->get("tempID")) {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("placeholders", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [2, 3])
                ->get();
        } else {
            $mainAds = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("placeholders", "landing")
                ->orderBy('type_order', 'ASC')
                ->whereIn("show_for", [1, 3])
                ->get();
        }
        $adsReturn = [];
        foreach ($mainAds as $ad) {
            $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_ref");
            $adsReturn[] = [
                "holderRef" => $image ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(null),
                "holderText" => isset($ad->content_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "content_text") : NULL,
                "holderLink" => isset($ad->content_link) ? $ad->content_link : NULL,
            ];
        }
        $return['data'] = $adsReturn;
        if (count($adsReturn) > 0) {
            $return['status'] = TRUE;
            $return['code'] = 200;
        } else {
            $return['status'] = FALSE;
            $return['code'] = 204;
            $return['msg'] = 'NoData';
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch home page placeholders"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function internalPageAds()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Announcement", 'function_name' => "Internal page adertisment"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetching internal page advertisments"]);
        $mainAds = \OlaHub\Models\AdSlotsCountries::whereHas('mainSlotDetails', function ($q) {
            $q->where('parent_id', '2');
        })->orderBy('slot_order', 'ASC')->get();
        $adsReturn = [];
        foreach ($mainAds as $one) {
            $ad = AdsMongo::where('country', app('session')->get('def_country')->id)->where('slot', $one->id)->first();
            if ($ad) {
                AdsMongo::setAdView($ad);
                $adsReturn[] = [
                    "adToken" => isset($ad->token) ? $ad->token : NULL,
                    "adSlot" => isset($ad->slot) ? $ad->slot : 0,
                    "adRef" => isset($ad->content_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($ad->content_ref) : NULL,
                    "adText" => isset($ad->content_text) ? $ad->content_text : NULL,
                    "adLink" => isset($ad->access_link) ? $ad->access_link : NULL,
                    "adTarget" => "blank",
                ];
            } else {
                $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($one, "default_image");
                $adsReturn[] = [
                    "adToken" => NULL,
                    "adSlot" => $one->id,
                    "adRef" => isset($one->default_image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image) : NULL,
                    "adText" => isset($one->default_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($one, "default_text") : NULL,
                    "adLink" => isset($one->default_url) ? $one->default_url : NULL,
                    "adTarget" => "self",
                ];
            }
        }
        $return['data'] = $adsReturn;
        $return['status'] = TRUE;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch internal page advertisments"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function likeSponserAd()
    {


        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "Announcement", 'function_name' => "Like sponser advertisment"]);

        $pointsLike = \DB::table('points_exchange_rates')->where('country_id', app('session')->get('def_country')->id)->first();
        if (isset($this->requestData['sponser'])) {
            $campaign = \OlaHub\Models\Ads::where('campign_token', $this->requestData['sponser'])->first();
            if ($campaign && $campaign->max_likes > $campaign->current_likes) {
                $oldLike = \OlaHub\UserPortal\Models\UserPoints::where('user_id', app('session')->get('tempID'))
                    ->where('country_id', app('session')->get('def_country')->id)
                    ->where('campign_id', $campaign->id)
                    ->first();
                if (!$oldLike) {
                    $setLike = new \OlaHub\UserPortal\Models\UserPoints;
                    $setLike->user_id = app('session')->get('tempID');
                    $setLike->country_id = app('session')->get('def_country')->id;
                    $setLike->campign_id = $campaign->id;
                    $setLike->points_collected = $pointsLike->number_of_unites;
                    $setLike->collect_date = date('Y-m-d');
                    $setLike->save();
                    $campaign->current_likes++;
                    $campaign->save();

                    AdsMongo::where('campaign_id', $campaign->id)->push('likers', app('session')->get('tempID'));
                }
            }
        }
        $userPoints = \OlaHub\UserPortal\Models\UserPoints::selectRaw('SUM(points_collected) as pointsSum')->where('user_id', app('session')->get('tempID'))->first();
        $pointsReedem = $userPoints->pointsSum * $pointsLike->sell_price;
        $userVoucher = \OlaHub\UserPortal\Models\UserVouchers::where('user_id', app('session')->get('tempID'))->first();
        $userBalance = 0;
        if ($userVoucher) {
            $userBalance = $userVoucher->voucher_balance;
        }
        $balanceNumber = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($userBalance + $pointsReedem);
        $return = ['status' => TRUE, 'msg' => 'likedPointsAdded', 'points' => $userPoints->pointsSum, "balanceNumber" => $balanceNumber, 'code' => 200];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }
}
