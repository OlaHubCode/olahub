<?php

namespace OlaHub\UserPortal\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use \OlaHub\UserPortal\Models\Post;
use OlaHub\UserPortal\Models\Occasion;
use Irazasyed\LaravelGAMP\Facades\GAMP;


class OlaHubGeneralController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;
    private $userInfo;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = (object) $return['requestData'];
        $this->requestFilter = (object) $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->requestShareData = $return['requestData'];
        $this->userInfo = null;
    }
    public function contactUs()
    {

        (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendContactUsEmail((Array) $this->requestData);
        return response(['status' => true, 'msg' => 'Data send successfully', 'code' => 200], 200);
    }

    public function sideBarAds()
    {


        $sponsers_arr = [];

        $timelinePosts = DB::table('campaign_slot_prices')->where('country_id', app('session')->get('def_country')->id)->where('is_post', '1')->inRandomOrder()->limit(10)->get();
        foreach ($timelinePosts as $onePost) {

            $sponsers = \OlaHub\Models\AdsMongo::where('slot', $onePost->id)->where('country', app('session')->get('def_country')->id)->orderBy('id', 'RAND()')->get();

            foreach ($sponsers as $one) {
                $campaign = \OlaHub\Models\Ads::where('campign_token', $one->token)->first();
                $liked = 0;
                if ($campaign) {
                    $oldLike = \OlaHub\UserPortal\Models\UserPoints::where('user_id', app('session')->get('tempID'))
                        ->where('country_id', app('session')->get('def_country')->id)
                        ->where('campign_id', $campaign->id)
                        ->first();
                    if ($oldLike) {
                        $liked = 1;
                    }
                }

                $sponsers_arr[] = [
                    'type' => 'sponser',
                    "adToken" => isset($one->token) ? $one->token : NULL,
                    'updated_at' => isset($one->updated_at) ? $one->updated_at : 0,
                    'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($one->created_at),
                    'post' => isset($one->_id) ? $one->_id : 0,
                    "adSlot" => isset($one->slot) ? $one->slot : 0,
                    "adRef" => isset($one->content_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->content_ref) : NULL,
                    "adText" => isset($one->content_text) ? $one->content_text : NULL,
                    "adLink" => isset($one->access_link) ? $one->access_link : NULL,
                    "liked" => $liked,
                ];
            }
        }
        return ($sponsers_arr);
    }



    public function setAdsStatisticsData($getFrom)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Set Ads statistics data"]);

        $request = \Illuminate\Http\Request::capture();
        $userIP = $request->ip();
        $userBrowser = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getUserBrowserAndOS($request->userAgent());

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start checking if there are old visits data"]);
        $oldVisit = \DB::table("ads_from_statistics")->where('from_ip', $userIP)->where('browser_name', $userBrowser)->where("come_from", $getFrom)->first();

        if (!$oldVisit) {
            \DB::table('ads_from_statistics')->insert(
                ['from_ip' => $userIP, 'browser_name' => $userBrowser, "come_from" => $getFrom, "visit_date" => date("Y-m-d")]
            );
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ["status" => true]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End checking if there are old visits data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(["status" => true], 200);
    }
    public function rightSideAds()
    {




        $ads = \OlaHub\Models\AdSlotsCountries::whereIn('id', [5, 35])
            ->inRandomOrder()
            ->limit(1)
            ->get();
        $adsReturn = [];
        foreach ($ads as $ad) {
            $image = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "default_image");
            if (\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkContentUrl($image)) {
                $adsReturn[] = [
                    "sliderRef" =>  isset($ad->default_image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($image) : NULL,
                    "sliderText" => isset($ad->content_text) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($ad, "default_text") : NULL,
                    "sliderLink" => isset($ad->default_url) ? $ad->default_url : NULL,

                ];
            }
        }
        return response(["status" => true, 'data' => $adsReturn]);
    }

    public function getCities($regionId)
    {
        $cities = \OlaHub\UserPortal\Models\ShippingCities::where('region_id', $regionId)->get();
        $result = [];
        foreach ($cities as $city) {
            $result[] = [
                'key' => $city->id,
                'value' => $city->id,
                'text' => $city->name,
            ];
        }
        $return['cities'] = $result;
        $return['status'] = true;

        return response($return);
    }
    public function getAllCountries()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "getAllCountries"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start getting countries data"]);

        $actionData = ["action_name" => "Get All countries"];
        $countries = \OlaHub\UserPortal\Models\Country::get();
        if ($countries->count() < 1) {
            throw new NotAcceptableHttpException(405);
        }
        $return['countries'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler');
        $allCountries = \OlaHub\UserPortal\Models\ShippingCountries::selectRaw("countries.name as text, countries.id as value, phonecode, LOWER(code) as flag, LOWER(code) as code")
            ->where('phonecode', '!=', "")
            ->join('countries', 'countries.id', 'shipping_countries.olahub_country_id')
            ->orderBy('shipping_countries.name', 'asc')->get();
        $allCountriesDropDown = \OlaHub\UserPortal\Models\ShippingCountries::selectRaw("countries.name as text, countries.id as value,LOWER(code) as flag")
            ->where('phonecode', '!=', "")

            ->join('countries', 'countries.id', 'shipping_countries.olahub_country_id')
            ->orderBy('shipping_countries.name', 'asc')->get();
        foreach ($allCountries as $country) {
            $country->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'text');
            // $country->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'text') . " ($country->phonecode)";
        }
        foreach ($allCountriesDropDown as $A) {
            $A->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($A, 'text');
            // $country->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'text') . " ($country->phonecode)";
        }

        $return['allCountries'] = $allCountries;
        $return['allCountriesDropDown'] = $allCountriesDropDown;
        $actionData["action_endData"] = json_encode(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler'));
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData($actionData);

        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End getting countries data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getAllListedCountries()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "getAllListedCountries"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start getting all countries data in DB"]);

        $actionData = ["action_name" => "Get All countries in DB"];
        $countries = \OlaHub\UserPortal\Models\Country::withoutGlobalScope("countrySupported")->orderBy("name", "ASC")->get();
        if ($countries->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return['countries'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler');

        $actionData["action_endData"] = json_encode(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler'));
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData($actionData);

        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End getting countries data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getAllUnsupportCountries()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get all unsupported countries"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start getting unsupported countries data"]);

        $countries = \OlaHub\UserPortal\Models\Country::withoutGlobalScope('countrySupported')->where('is_published', '0')->where('is_supported', '0')->get();
        if ($countries->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return['countries'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End getting unsupported countries data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getSocialAccounts()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get social accounts"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start getting social accounts data"]);

        $social = \OlaHub\UserPortal\Models\CompanyStaticData::where("type", "social")->whereNotNull("content_link")->get();
        if ($social->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $socialReturn = [];
        foreach ($social as $one) {
            $socialReturn[] = [
                "socialType" => isset($one->second_type) ? $one->second_type : null,
                "socialTitle" => isset($one->content_text) ? $one->content_text : null,
                "socialLink" => isset($one->content_link) ? $one->content_link : null,
            ];
        }

        $return['data'] = $socialReturn;
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End getting social accounts data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getAllCommuntites()
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get all communites"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch home page communities data"]);

        $generalCommunities = \OlaHub\UserPortal\Models\groups::where('privacy', 3)->where('olahub_community', "!=", 1)->take(9)->orderBy("total_members", "DESC")->get();
        $olahubCommunities = \OlaHub\UserPortal\Models\groups::where('olahub_community', 1)->whereIn("countries", [app("session")->get("def_country")->id])->orderBy("total_members", "DESC")->get();
        if ($generalCommunities->count() < 1 && $olahubCommunities->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }

        $return['homeComm'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($generalCommunities, '\OlaHub\UserPortal\ResponseHandlers\CommunitiesForLandingPageResponseHandler');
        $return['olahubComm'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($olahubCommunities, '\OlaHub\UserPortal\ResponseHandlers\CommunitiesForLandingPageResponseHandler');

        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch home page communities data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getOlaHubCommuntites()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get OlaHub communities"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch Olahub communtities data"]);

        $communities = \OlaHub\UserPortal\Models\groups::where('olahub_community', 1)->whereIn("countries", [app("session")->get("def_country")->id])->orderBy("total_members", "DESC")->get();
        if ($communities->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }

        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($communities, '\OlaHub\UserPortal\ResponseHandlers\CommunitiesForLandingPageResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch Olahub communtities data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getAllInterests()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get all Interests"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch interest data"]);
        $interests = \OlaHub\UserPortal\Models\Interests::get();
        // $interests = \OlaHub\UserPortal\Models\Interests::whereIn('countries', [app('session')->get('def_country')->id])->get();
        if ($interests->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($interests, '\OlaHub\UserPortal\ResponseHandlers\InterestsForPrequestFormsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch interest data"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getStaticPage($type)
    {

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get static page"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch static pages", "action_startData" => $type]);
        $page = \OlaHub\UserPortal\Models\StaticPages::where('type', $type)->first();
        if (!$page) {
            throw new NotAcceptableHttpException(404);
        }
        $pageData = [
            "content" => $page->content_text,
        ];
        $return['data'] = $pageData;
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch static pages"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }
    public function getFAQ(){

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "FAQ"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch FAQ", "action_startData" => 'FAQ']);
        // $faq = \OlaHub\UserPortal\Models\FAQ::with('cateData')->get();
        $faq = \OlaHub\UserPortal\Models\FaqCategory::with('faq')->get();
        if (!$faq) {
            throw new NotAcceptableHttpException(404);
        }

        $return['data'] = $faq;
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch static pages"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);

    }

    public function getUserNotification()
    {
        $week = 'updated_at	BETWEEN DATE_ADD(now(), INTERVAL -300 DAY) AND now()';
        $sessionUserId = (int) app('session')->get('tempID');
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get user notification"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch user notification"]);
        $notification = \OlaHub\UserPortal\Models\Notifications::with('userData')->where('user_id', (int) app('session')->get('tempID'))->orderBy("created_at", "DESC")->paginate(20);


        $newItemscnotification = \OlaHub\UserPortal\Models\UserNotificationNewItems::whereRaw($week)
            ->whereRaw("FIND_IN_SET($sessionUserId,user_id)")
            ->inRandomOrder()
            ->limit(2)
            ->groupBy('followed_slug')
            ->get();
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check notification existance"]);
        //return($newItemscnotification);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check notification existance"]);
        $allNotifications = [];
        $newItemsNotifications = [];

        if ($newItemscnotification->count() > 0) {
            foreach ($newItemscnotification as $one) {
                switch ($one->type) {
                    case "new_multi_brand_items":
                        $brandData = @$one->brandData->first();
                        $newItemsNotifications[] = [
                            "followed_slug" => $one->followed_slug,
                            "id" => $one->id,
                            "type" => $one->type,
                            "content" => $one->content,
                            "user_name" => isset($brandData) ? $brandData["name"] : "NULL",
                            "avatar_url" => isset($brandData["image_ref"]) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brandData["image_ref"]) : null,
                            "for_user" => $one->user_id,
                        ];
                        break;
                    case "new_multi_category_items":
                        $category = DB::table('catalog_item_categories')->where('category_slug', $one->followed_slug)->first();
                        $newItemsNotifications[] = [
                            "followed_slug" => $one->followed_slug,
                            "id" => $one->id,
                            "type" => $one->type,
                            "content" => $one->content,
                            "user_name" => isset($category) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, "name") : "NULL",
                            "avatar_url" => isset($category) ? ($category->category_slug) : "",
                            "for_user" => $one->user_id,
                        ];
                        break;
                    case "new_multi_interest_items":
                        $interestData = @$one->interestData->first();
                        $newItemsNotifications[] = [
                            "followed_slug" => $one->followed_slug,
                            "id" => $one->id,
                            "type" => $one->type,
                            "content" => $one->content,
                            "user_name" => isset($interestData) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interestData, "name") : "NULL",
                            "avatar_url" => isset($interestData["image_ref"]) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interestData["image_ref"]) : null,
                            "for_user" => $one->user_id,
                        ];
                        break;

                    case "new_multi_occasion_items":
                        $occasionS = DB::table('occasion_types')->where('occasion_slug', $one->followed_slug)->first();
                        $newItemsNotifications[] = [
                            "followed_slug" => $one->followed_slug,
                            "id" => $one->id,
                            "type" => $one->type,
                            "content" => $one->content,
                            "user_name" => isset($occasionS) ?  \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasionS, "name") : "NULL",
                            "avatar_url" => isset($occasionS->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasionS->logo_ref) : NULL,
                            "for_user" => $one->user_id,
                        ];
                        break;
                }
            }
        }

        if ($notification->count() > 0) {
            // dd($newItemscnotification);
            // return($newItemscnotification);
            foreach ($notification as $one) {
                if ($one->content == 'notifi_post_comment_for_follower' || $one->content == 'notifi_post_like_for_follower') {
                    $postID = $one->post_id;
                    $post = Post::where('post_id', $postID)->first();
                    $posterName = \OlaHub\UserPortal\Models\UserModel::where('id',  $post->user_id)->first();
                }
                $userData = @$one["userData"][0];
                $groupData = @$one["groupData"][0];
                $celebrationData = @$one["celebrationData"][0];
                $registryData = @$one["registryData"][0];
                $allNotifications[] = [
                    "id" => $one->id,
                    "type" => $one->type,
                    "content" => $one->content,
                    "celebration_id" => $one->celebration_id,
                    "post_id" => $one->post_id,
                    "group_id" => $one->group_id,
                    "registry_id" => $one->registry_id,
                    "user_name" => isset($userData) ? $userData["first_name"] . " " . $userData["last_name"] : "NULL",
                    "community_title" => @$groupData["name"],
                    "celebration_title" => @$celebrationData["title"],
                    "registry_title" => @$registryData["title"],
                    "profile_url" => $userData["profile_url"],
                    "avatar_url" => isset($userData["profile_picture"]) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData["profile_picture"]) : null,
                    "read" => $one->read,
                    "for_user" => $one->user_id,
                    "poster_name" => isset($posterName) ?  "$posterName->first_name $posterName->last_name" : ""
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $notification]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End check notification existance"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch user notification"]);
            $data = [
                'newItemsNotifications' => $newItemsNotifications,
                'allNotifications' => $allNotifications,
                'lastPage' => $notification->lastPage()

            ];
            return $data;
        } else {
            $return = ['status' => false, 'no_data' => '1', 'msg' => 'NoData', 'code' => 204];
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return $return;
        }
    }

    public function getAllNotifications()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get all notifications"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch all notification"]);
        $notification = \OlaHub\UserPortal\Models\Notifications::where('user_id', app('session')->get('tempID'))->orderBy("created_at", "DESC")->get();
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check notification existance"]);
        if ($notification->count() > 0) {
            $i = 0;
            foreach ($notification as $one) {
                $image = $one->avatar_url;
                if (strstr($image, "http://23.97.242.159:8080/images/")) {
                    $one->avatar_url = str_replace("http://23.97.242.159:8080/images/", "", $one->avatar_url);
                    $one->save();
                } elseif (strstr($image, "http://23.100.10.45:8080/images/")) {
                    $one->avatar_url = str_replace("http://23.100.10.45:8080/images/", "", $one->avatar_url);
                    $one->save();
                } elseif (strstr($image, "http://localhost/userproject/images/defaults/5b5d862798ff2.png")) {
                    $one->avatar_url = str_replace("http://localhost/userproject/images/defaults/5b5d862798ff2.png", false, $one->avatar_url);
                    $one->save();
                } elseif (strstr($image, "http://localhost/userproject/images/")) {
                    $one->avatar_url = str_replace("http://localhost/userproject/images/", "", $one->avatar_url);
                    $one->save();
                } elseif (strstr($image, "http://23.97.242.159:8080/temp_photos/defaults/5b5d862798ff2.jpg")) {
                    $one->avatar_url = str_replace("http://23.97.242.159:8080/temp_photos/defaults/5b5d862798ff2.jpg", "", $one->avatar_url);
                    $one->save();
                } elseif (strstr($image, "http://23.97.242.159:8080/temp_photos/")) {
                    $one->avatar_url = str_replace("http://23.97.242.159:8080/temp_photos/", "", $one->avatar_url);
                    $one->save();
                }

                $notification[$i]->avatar_url = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->avatar_url);
                $i++;
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $notification]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch user notification"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End check notification existance"]);
            return $notification;
        } else {

            $return = ['status' => false, 'no_data' => '1', 'msg' => 'NoData', 'code' => 204];
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return $return;
        }
    }

    public function readNotification()
    {
        $sessionUserId = (int) app('session')->get('tempID');

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Read notification"]);

        if (isset($this->requestData->notificationId) && $this->requestData->notificationId) {
            if ($this->requestData->notificationId == 'all') {
                \OlaHub\UserPortal\Models\Notifications::where('user_id', app('session')->get('tempID'))->update(['read' => 1]);

                // \OlaHub\UserPortal\Models\UserNotificationNewItems::->whereRaw("!FIND_IN_SET($sessionUserId,read_items)")->update(['read_items' => 0]);
                return ['status' => true, 'msg' => 'Notifications has been read', 'code' => 200];
            }
            // else if($this->requestData->type=="newItems"){
            //     $notification = \OlaHub\UserPortal\Models\UserNotificationNewItems::where('user_id', app('session')->get('tempID'))->find($this->requestData->notificationId);
            //     if ($notification) {
            //         $notification->save();
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'Notification has been read', 'code' => 200]]);
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

            //         return ['status' => true, 'msg' => 'Notification has been read', 'code' => 200];
            //         (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End making notification read"]);
            //     }
            // }

            else {

                $notification = \OlaHub\UserPortal\Models\Notifications::where('user_id', app('session')->get('tempID'))->find($this->requestData->notificationId);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start making notification read"]);
                if ($notification) {
                    $notification->read = 1;
                    $notification->save();
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'Notification has been read', 'code' => 200]]);
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

                    return ['status' => true, 'msg' => 'Notification has been read', 'code' => 200];
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End making notification read"]);
                }
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return ['status' => false, 'msg' => 'NoData', 'code' => 204];
    }

    public function getCodeCountries()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get code countries"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch countries"]);
        $countries = \OlaHub\UserPortal\Models\Country::get();
        if ($countries->count() < 1) {
            throw new NotAcceptableHttpException(404);
        }
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesCodeForPrequestFormsResponseHandler');
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch countries"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function checkUserCountry()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Check user country"]);

        $defCountryCode = 'JO';
        $getIPInfo = new \OlaHub\UserPortal\Helpers\getIPInfo();
        $countryCode = $getIPInfo->ipData('countrycode');
        if ($countryCode && strlen($defCountryCode) == 2) {
            $defCountryCode = $countryCode;
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start set user country"]);
        $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $countryCode)->where('is_supported', '1')->where('is_published', '1')->first();
        if (!$country) {
            $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', 'JO')->where('is_supported', '1')->where('is_published', '1')->first();
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'country' => strtoupper($country->two_letter_iso_code), 'code' => 200]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End set user country"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response([
            'status' => true,
            'country' => strtolower($country->two_letter_iso_code),
            'country_id' => $country->id,
            'countryName' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'name'),
            'code' => 200,
        ], 200);
    }

    public function checkUserMerchant()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Check user merchant"]);

        $data = [
            "isMerchantUser" => false,
            "isStoreUser" => false,
        ];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check user merchant"]);
        if (app('session')->get('tempData')->for_merchant) {
            $data = [
                "isMerchantUser" => true,
            ];
        }
        if (app('session')->get('tempData')->for_store) {
            $data = [
                "isStoreUser" => true,
            ];
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'data' => $data, 'code' => 200]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End Check user merchant"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => true, 'data' => $data, 'code' => 200], 200);
    }

    public function searchUsers()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Search users"]);

        $return = ['status' => false, 'no_data' => '1', 'msg' => 'NoData', 'code' => 204];
        $q = 'a';
        if (isset($this->requestFilter->word) && strlen($this->requestFilter->word) > 2/* && strlen($this->requestFilter->word) % 3 == 0 */) {
            $q = mb_strtolower($this->requestFilter->word);

            $event = false;
            if (isset($this->requestFilter->celebration) && $this->requestFilter->celebration > 0) {
                $event = $this->requestFilter->celebration;
            }
            $group = false;
            if (isset($this->requestFilter->group) && $this->requestFilter->group > 0) {
                $group = $this->requestFilter->group;
            }
            $count = 15;
            if (isset($this->requestFilter->total) && $this->requestFilter->total > 0) {
                $count = $this->requestFilter->total;
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start search users"]);
            $users = \OlaHub\UserPortal\Models\UserModel::searchUsers($q, $event, $group, $count);
            if ($users['data']->count() > 0) {
                $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($users['data'], '\OlaHub\UserPortal\ResponseHandlers\searchUsersForPrequestFormsResponseHandler');
                $return['status'] = true;
                $return['code'] = 200;
            }
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End search users"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function searchAll()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Search All"]);

        $return = ['status' => false, 'no_data' => '1', 'msg' => 'NoData', 'code' => 204];
        $q = 'a';
        $searchData = [];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search all"]);
        if (isset($this->requestFilter->word) && strlen($this->requestFilter->word) > 1) {
            $q = mb_strtolower($this->requestFilter->word);
            $searchQuery = [];

            // brands
            $searchQuery[] = "select count(id) as search from merchant_stors
            where LOWER(`name`) like '%" . $q . "%' or LOWER(`name`) sounds like '" . $q . "'";

            // designers
            $searchQuery[] = "select count(id) as search from designers
            where LOWER(`brand_name`) like '%" . $q . "%' or LOWER(`brand_name`) sounds like '" . $q . "'";

            // items
            $searchQuery[] = "select count(id) as search from catalog_items
            where LOWER(`name`) like '%" . $q . "%' or LOWER(`name`) sounds like '" . $q . "'";

            // designer items
            $searchQuery[] = "select count(id) as search from designer_items
            where LOWER(`name`) like '%" . $q . "%' or LOWER(`name`) sounds like '" . $q . "'";

            if (app('session')->get('tempID')) {
                // users
                $searchQuery[] = "select count(id) as search from users
                where( (LOWER(`email`) like '%" . $q . "%' 
                or mobile_no like '%" . $q . "%'
                or concat(LOWER(`first_name`), ' ', LOWER(`last_name`)) like '" . $q . "'
                or LOWER(`first_name`) sounds like '" . $q . "'
                or LOWER(`last_name`) sounds like '" . $q . "'))
                and id <> " . app('session')->get('tempID') . " and is_active = 1";

                // groups
                $searchQuery[] = "select count(id) as search from groups
                where LOWER(`name`) sounds like '" . $q . "'
                or LOWER(`description`) sounds like '" . $q . "'";
            }
            $handle = \DB::select(\DB::raw(implode(' union all ', $searchQuery)));
            //            var_dump($handle);
            // brands
            if ($handle[0]->search > 0) {
                $searchData[] = [
                    "type" => "brands",
                ];
            }
            // designers
            if ($handle[1]->search > 0) {
                $searchData[] = [
                    "type" => "designers",
                ];
            }
            // items
            if ($handle[2]->search > 0) {
                $searchData[] = [
                    "type" => "items",
                ];
            }
            // designer items
            if ($handle[3]->search > 0) {
                $searchData[] = [
                    "type" => "desginer_items",
                ];
            }
            if (app('session')->get('tempID')) {
                // users
                if ($handle[4]->search > 0) {
                    $searchData[] = [
                        "type" => "users",
                    ];
                }
                // groups
                if ($handle[5]->search > 0) {
                    $searchData[] = [
                        "type" => "groups",
                    ];
                }
            }

            $ditems = [];
            $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, 5);
            if ($items["data"]) {
                $ditems["items"] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items["data"], '\OlaHub\UserPortal\ResponseHandlers\ItemSearchResponseHandler')['data'];
            }

            $designerItems = \OlaHub\UserPortal\Models\DesignerItems::searchItem($q, 5);
            if ($designerItems) {
                $ditems["designerItems"] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($designerItems, '\OlaHub\UserPortal\ResponseHandlers\DesignerItemsSearchResponseHandler')['data'];
            }
        }
        $return = [
            'status' => true,
            'data' => $searchData,
            'items' => $ditems,
            'code' => 200,
        ];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End search"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function searchAllFilters()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Search all filters"]);

        $return = ['status' => false, 'no_data' => '1', 'msg' => 'NoData', 'code' => 204];
        $q = 'a';
        $count = 24;
        $searchData = [];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start search according filter"]);
        if ((isset($this->requestFilter->word) && strlen($this->requestFilter->word) > 1) && isset($this->requestFilter->type) && strlen($this->requestFilter->type) > 1) {
            $q = mb_strtolower($this->requestFilter->word);
            $type = $this->requestFilter->type;
            $is_numeric = is_numeric($this->requestFilter->word);

            $find1 = strpos($this->requestFilter->word, '@');
            $find2 = strpos($this->requestFilter->word, '.');
            if (($find1 !== false && $find2 !== false) || $is_numeric) {
                $type = "users";
            }

            switch ($type) {
                case "users":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search users filter"]);
                    if (app('session')->get('tempID')) {
                        $users = \OlaHub\UserPortal\Models\UserModel::searchUsers($q, false, false, $count, TRUE);
                        if ($users["data"]->count() > 0) {
                            $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($users["data"], '\OlaHub\UserPortal\ResponseHandlers\UserSearchResponseHandler');
                        }
                        if ($users["related"]) {
                            $searchData["related"] = $users["related"];
                        }
                    }
                    break;
                case "groups":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search groups filter"]);
                    if (app('session')->get('tempID')) {
                        $groups = \OlaHub\UserPortal\Models\groups::searchGroups($q, $count);
                        if ($groups->count() > 0) {
                            $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($groups, '\OlaHub\UserPortal\ResponseHandlers\GroupSearchResponseHandler');
                        }
                    }
                    break;
                case "brands":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search brands filter"]);
                    $brands = \OlaHub\UserPortal\Models\Brand::searchBrands($q, $count);
                    if ($brands->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandSearchResponseHandler');
                    }
                    break;
                case "items":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search items filter"]);
                    $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, $count, true);
                    if ($items["data"]->count()) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items["data"], '\OlaHub\UserPortal\ResponseHandlers\ItemSearchResponseHandler');
                    }
                    if ($items["related"]) {
                        $searchData["related"] = $items["related"];
                    }
                    break;
                case "desginer_items":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search desginer items filter"]);
                    $designerItems = \OlaHub\UserPortal\Models\DesignerItems::searchItem($q, $count);
                    if ($designerItems->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($designerItems, '\OlaHub\UserPortal\ResponseHandlers\DesignerItemsSearchResponseHandler');
                    }
                    break;
                case "designers":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search designers filter"]);
                    $desginers = \OlaHub\UserPortal\Models\Designer::searchDesigners($q, $count);
                    if ($desginers->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($desginers, '\OlaHub\UserPortal\ResponseHandlers\DesignersSearchResponseHandler');
                    }
                    break;
                default:
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search classification filter"]);
                    $classification = \OlaHub\UserPortal\Models\Classification::where("class_slug", $type)->first();
                    if ($classification) {
                        $items = \OlaHub\UserPortal\Models\CatalogItem::searchItemByClassification($q, $classification->class_slug, $count);
                        if ($items && $items->count() > 0) {
                            $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ClassificationSearchResponseHandler');
                        }
                    }
                    break;
            }
        }
        if (count($searchData) > 0) {
            $return = ['status' => true, 'data' => $searchData, 'code' => 200];
        }

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End search according filter"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    private function setDefImageData($item)
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Set default image data"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start set default image data"]);
        $images = $item->images;
        if ($images->count() > 0) {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref);
        } else {
            return \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End set default image data"]);
    }

    public function inviteNewUser()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Invite new user"]);

        $validator = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::validateData(\OlaHub\UserPortal\Models\UserModel::$columnsInvitationMaping, (array) $this->requestData);
        if (isset($validator['status']) && !$validator['status']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'someData', 'code' => 406, 'errorData' => $validator['data']], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start invite new user"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Check email existance of new user"]);
        // if (isset($this->requestData->userEmail) && strlen($this->requestData->userEmail) > 3) {
        //     $checkExist = \OlaHub\UserPortal\Models\UserModel::where("email", $this->requestData->userEmail)->first();
        //     if ($checkExist) {
        //         (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['userEmail' => ['validation.unique.email']]]]);
        //         (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        //         return response(['status' => false, 'msg' => 'emailExist', 'code' => 406, 'errorData' => ['userEmail' => ['validation.unique.email']]], 200);
        //     }
        //     $checkTemp = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->where("email", $this->requestData->userEmail)->first();
        //     if ($checkTemp) {
        //         $user = $checkTemp;
        //     }
        // }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Check phoneNumber existance of new user"]);
        if (isset($this->requestData->userPhoneNumber) && strlen($this->requestData->userPhoneNumber) > 3) {
            $phone = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData->userPhoneNumber);
            $checkExist = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")
                ->where("mobile_no", $phone)
                ->where("country_id", $this->requestData->userCountry)->first();
            if ($checkExist) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]], 200);
            }
            // $checkTemp = \OlaHub\UserPortal\Models\UserModel::withoutGlobalScope("notTemp")->where("mobile_no", $this->requestData->userPhoneNumber)->first();
            // if ($checkTemp) {
            //     if (isset($this->requestData->userEmail) && strlen($this->requestData->userEmail) > 3) {
            //         if ($user) {
            //             if ($user->id != $checkTemp->id) {
            //                 (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]]]);
            //                 (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            //                 return response(['status' => false, 'msg' => 'phoneExist', 'code' => 406, 'errorData' => ['userPhoneNumber' => ['validation.unique.phone']]], 200);
            //             }
            //         } else {
            //             $user = $checkTemp;
            //         }
            //     } else {
            //         $user = $checkTemp;
            //     }
            // }
        }
        $user = new \OlaHub\UserPortal\Models\UserModel;
        if (!empty($this->requestData->userPhoneNumber)) {
            $this->requestData->userPhoneNumber = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData->userPhoneNumber);
        }

        foreach ($this->requestData as $input => $value) {
            if (isset(\OlaHub\UserPortal\Models\UserModel::$columnsMaping[$input])) {
                $user->{\OlaHub\UserPortal\Helpers\CommonHelper::getColumnName(\OlaHub\UserPortal\Models\UserModel::$columnsMaping, $input)} = $value;
            }
        }
        $secureHelper = new \OlaHub\UserPortal\Helpers\SecureHelper;
        $user->password = $secureHelper->setPasswordHashing(\OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6));
        $user->invited_by = app('session')->get('tempID');
        $user->is_first_login = 1;
        $user->country_id = $this->requestData->userCountry;
        if ($user->save()) {
            if (isset($this->requestData->isFriendsInvite) && $this->requestData->isFriendsInvite) {
                $password = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::randomString(6);
                $userData = app('session')->get('tempData');
                $user->password = $password;
                $user->save();
                if (isset($this->requestData->userEmail) && $this->requestData->userEmail && isset($this->requestData->userPhoneNumber) && $this->requestData->userPhoneNumber) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserInvition($user, $userData->first_name . ' ' . $userData->last_name, $password);
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserInvition($user, $userData->first_name . ' ' . $userData->last_name, $password);
                } else if (isset($this->requestData->userPhoneNumber) && $this->requestData->userPhoneNumber) {
                    (new \OlaHub\UserPortal\Helpers\SmsHelper)->sendNotRegisterUserInvition($user, $userData->first_name . ' ' . $userData->last_name, $password);
                } else if (isset($this->requestData->userEmail) && $this->requestData->userEmail) {
                    (new \OlaHub\UserPortal\Helpers\EmailHelper)->sendNotRegisterUserInvition($user, $userData->first_name . ' ' . $userData->last_name, $password);
                }
            }

            $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseItem($user, '\OlaHub\UserPortal\ResponseHandlers\UsersResponseHandler');
            $return['status'] = true;
            $return['code'] = 200;
        } else {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'InternalServerError', 'code' => 500]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'InternalServerError', 'code' => 500], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End invite new user"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return $return;
    }

    public function sendSellWithUsEmail()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Send sell with us Email"]);

        $return = ['status' => false, 'msg' => 'fillAllFields', 'code' => 406, 'errorData' => []];
        $supported = false;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start send sell with us email"]);
        if (isset($this->requestData->isNotifi) && !empty($this->requestData->isNotifi) && ($this->requestData->isNotifi == 0)) {
            if (isset($this->requestData->userEmail) && !empty($this->requestData->userEmail) && isset($this->requestData->userName) && !empty($this->requestData->userName) && isset($this->requestData->userPhoneNumber) && !empty($this->requestData->userPhoneNumber)) {
                $country = \OlaHub\UserPortal\Models\Country::where('two_letter_iso_code', $this->requestData->country)->where('is_supported', '1')->where('is_published', '1')->first();
                if ($country) {
                    $supported = true;
                    $inviteMerchant = new \OlaHub\UserPortal\Models\MerchantInvite;
                    $inviteMerchant->supplier_name = $this->requestData->userName;
                    $inviteMerchant->supplier_email = $this->requestData->userEmail;
                    $inviteMerchant->status = 6;
                    $inviteMerchant->country_id = $country->id;
                    $inviteMerchant->save();
                    $franchises = \OlaHub\UserPortal\Models\Franchise::where('country_id', app('session')->get('def_country')->id)->where('is_license', "1")->get();
                    if ($franchises->count()) {
                        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
                        $sendMail->template_code = 'ADM006';
                        $sendMail->replace = ['[merName]', '[merEmail]', '[merPhoneNum]'];
                        $sendMail->replace_with = [$this->requestData->userName, $this->requestData->userEmail, $this->requestData->userPhoneNumber];
                        foreach ($franchises as $franchise) {
                            if (isset($franchise->email) && strlen($franchise->email) > 5) {
                                $sendMail->to[] = [$franchise->email, "$franchise->first_name $franchise->last_name"];
                            }
                        }
                        $sendMail->send();
                    }
                    $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
                    $sendMail->template_code = 'MER002';
                    $sendMail->replace = ['[merName]'];
                    $sendMail->replace_with = [$this->requestData->userName];
                    $sendMail->to[] = [$this->requestData->userEmail, $this->requestData->userName];
                    $sendMail->send();
                    $return = ['status' => true, 'msg' => 'sentOurManagers', 'code' => 200];
                }
            }
        }

        if (!$supported) {
            $sellWithUsUnsupport = new \OlaHub\UserPortal\Models\SellWithUsUnsupport;
            $sellWithUsUnsupport->merchant_name = $this->requestData->userName;
            $sellWithUsUnsupport->merchant_email = $this->requestData->userEmail;
            $sellWithUsUnsupport->merchant_phone_no = $this->requestData->userPhoneNumber;
            $sellWithUsUnsupport->country_id = $this->requestData->country;
            $sellWithUsUnsupport->save();
            $return = ['status' => true, 'msg' => 'sentOurManagers', 'code' => 200];
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End sell with us email"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();

        return response($return, 200);
    }

    public function getUserTimeline(Request $request)
    {
        $page = (int) $request->input('page') || 1;
        $now = date('Y-m-d');
        $month = 'created_at BETWEEN DATE_ADD(CURRENT_DATE(), INTERVAL -300 DAY) AND CURRENT_DATE()';
        $monthC = 'catalog_items.created_at BETWEEN DATE_ADD(CURRENT_DATE(), INTERVAL -300 DAY) AND CURRENT_DATE()';
        $timeline = [];
        $friends = null;
        $all = [];
        $upcoming = [];
        $celebrations = [];
        $return = ['status' => true, 'code' => 200];
        // $user = \OlaHub\UserPortal\Models\UserModel::find(app('session')->get('tempID'));
        $user = app('session')->get('tempData');
        if ($user) {
            $this->userInfo = [
                'user_id' => $user->id,
                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture),
                'profile_url' => $user->profile_url,
                'username' => "$user->first_name $user->last_name",
            ];
            if ($page == 1) {
                $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                if (count($friends)) {
                    $friendsCalendar = \OlaHub\UserPortal\Models\CalendarModel::whereIn('user_id', $friends)->where('calender_date', "<=", date("Y-m-d H:i:s", strtotime("+7 days")))->where('calender_date', ">", date("Y-m-d H:i:s"))->orderBy('calender_date', 'desc')->get();
                    if (count($friendsCalendar)) {
                        $upcomingData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($friendsCalendar, '\OlaHub\UserPortal\ResponseHandlers\UpcomingEventsResponseHandler');
                        $upcoming = $upcomingData['data'];
                    }
                }
                $nonSeenGifts = \DB::table('billing_history')->select("*")->where('is_gift', 1)
                    ->where('gift_for', $user->id)
                    ->where('gift_date', $now)
                    ->where('seen', 0)
                    ->get();
                foreach ($nonSeenGifts as $gift) {
                    $gift_sender = \OlaHub\UserPortal\Models\UserModel::find($gift->id);
                    $gift_sender = array(
                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($gift_sender->profile_picture),
                        'profile_url' => $gift_sender->profile_url,
                        'username' => "$gift_sender->first_name $gift_sender->last_name",
                    );
                    $items = \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $gift->id)->get();
                    $nonSeenGiftsResponse = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');
                    $all[] = [
                        'type' => 'gift',
                        'gift_sender' => $gift_sender,
                        'message' => isset($gift->gift_message) ? $gift->gift_message : "",
                        'video' => isset($gift->gift_video_ref) ? $gift->gift_video_ref : "",
                        'items' => $nonSeenGiftsResponse['data'],
                    ];
                }
                if ($nonSeenGifts) {
                    \DB::table('billing_history')->where('is_gift', 1)
                        ->where('gift_for', app('session')->get('tempID'))
                        ->where('gift_date', $now)
                        ->update(["seen" => 1]);
                }

                //celebration
                try {
                    $nonSeenCelebrations = \OlaHub\UserPortal\Models\CelebrationModel::whereRaw('celebration_date <= now()')
                        ->where('seen', 0)
                        ->where('celebration_status', '>=', 3)
                        ->where('user_id', app('session')->get('tempID'))
                        ->orderBy('celebration_date', 'desc')->get();
                    if ($nonSeenCelebrations->count() > 0) {
                        foreach ($nonSeenCelebrations as $celebration) {
                            //users
                            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('celebration_id', $celebration->id)->get();
                            $cUsersNames = [];
                            $cUsers = [];
                            foreach ($participants as $participant) {
                                $u = \OlaHub\UserPortal\Models\UserModel::where('id', $participant->user_id)->first();
                                $video = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('created_by', $participant->id)->first();
                                $cUsersNames[] = "$u->first_name $u->last_name";
                                if (isset($video->reference) || isset($participant->personal_message)) {
                                    $cUsers[] = [
                                        "username" => "$u->first_name $u->last_name",
                                        "video" => isset($video->reference) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($video->reference) : null,
                                        "message" => $participant->personal_message,
                                    ];
                                }
                            }
                            //gifts
                            $bill = \DB::table('billing_history')->select("*")->where('pay_for', $celebration->id)->first();
                            $cItems = \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $bill->id)->get();
                            $cGifts = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($cItems, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');

                            $all[] = [
                                'type' => 'celebration',
                                'date' => $celebration->celebration_date,
                                'title' => $celebration->title,
                                'items' => $cGifts['data'],
                                'users' => $cUsersNames,
                                'media' => $cUsers,
                            ];
                        }
                        \OlaHub\UserPortal\Models\CelebrationModel::whereRaw('celebration_date <= now()')
                            ->where('seen', 0)
                            ->where('celebration_status', '>=', 3)
                            ->where('user_id', app('session')->get('tempID'))
                            ->update(["seen" => 1]);
                    }
                } catch (Exception $ex) {
                }
                //celebration media
                try {
                    $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->get();
                    if ($participants->count() > 0) {
                        foreach ($participants as $participant) {
                            $celebrationContents = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('celebration_id', $participant->celebration_id)->where('created_at', ">=", date("Y-m-d H:i:s", strtotime("-7 days")))->orderBy('created_at', 'desc')->get();
                            $type = '';
                            if ($celebrationContents->count() > 0) {
                                foreach ($celebrationContents as $celebrationContent) {
                                    $contentOwner = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('id', $celebrationContent->created_by)->first();
                                    $author = \OlaHub\UserPortal\Models\UserModel::where('id', $contentOwner->user_id)->first();
                                    if ($contentOwner && $author) {
                                        $authorName = "$author->first_name $author->last_name";
                                        $explodedData = explode('.', $celebrationContent->reference);
                                        $extension = end($explodedData);
                                        if (in_array(strtolower($extension), VIDEO_EXT)) {
                                            $type = 'video';
                                        } elseif (in_array($extension, IMAGE_EXT)) {
                                            $type = 'image';
                                        }
                                        $celebrations[] = [
                                            "type" => 'celebration',
                                            "id" => $celebrationContent->celebration_id,
                                            "mediaType" => $type,
                                            "content" => isset($celebrationContent->reference) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($celebrationContent->reference) : null,
                                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($celebrationContent->created_at),
                                            'user_info' => [
                                                'user_id' => $author->id,
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                                'username' => $authorName,
                                            ],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $ex) {
                }
            }

            // posts
            try {

                if (!$friends) {
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                }

                $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr($user->id);
                $posts = Post::where(function ($q) use ($friends, $myGroups) {
                    $q->where(function ($userPost) use ($friends) {
                        $userPost->whereIn('user_id', $friends);
                        $userPost->where('friend_id', null);
                    });
                    $q->orWhere(function ($userPost) {
                        $userPost->where('friend_id', app('session')->get('tempID'));
                    });
                    $q->orWhere(function ($userPost) use ($friends, $myGroups) {
                        $userPost->whereIn('user_id', $friends);
                        $userPost->whereIn('group_id', $myGroups);
                    });
                })->where('privacy', '!=', 3)->orderBy('created_at', 'desc')->paginate(20);

                if ($posts->count()) {
                    foreach ($posts as $post) {
                        $d = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseItem($post, '\OlaHub\UserPortal\ResponseHandlers\PostsResponseHandler');
                        $timeline[] = $d['data'];
                    }
                }
            } catch (Exception $ex) {
            }

            // liked items
            try {
                if (!$friends) {
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                }

                $likedItems = \OlaHub\UserPortal\Models\LikedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($friends) {
                        $q->where(function ($query) use ($friends) {
                            $query->whereIn('user_id', $friends);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($likedItems->count()) {
                    $filteredStoreItems = [];
                    $filteredDesignerItems = [];
                    foreach ($likedItems as $litem) {
                        if ($litem->item_type == 'store') {
                            if (!isset($filteredStoreItems[$litem->item_id])) {
                                $filteredStoreItems[$litem->item_id] = [];
                            }

                            array_push($filteredStoreItems[$litem->item_id], $litem->user_id);
                        } else {
                            if (!isset($filteredDesignerItems[$litem->item_id])) {
                                $filteredDesignerItems[$litem->item_id] = [];
                            }

                            array_push($filteredDesignerItems[$litem->item_id], $litem->user_id);
                        }
                    }
                    if (count($filteredStoreItems)) {
                        foreach ($filteredStoreItems as $item_id => $users) {
                            $uInfo = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $users)->get();
                            $uNames = [];
                            $fInfo = [
                                'username' => "",
                                'other' => 0,
                            ];
                            $uCount = $uInfo->count();
                            if ($uCount > 3) {
                                $x = 0;
                                while ($x < 3) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                                $fInfo['other'] = $uCount - 3;
                            } else {
                                $x = 0;
                                while ($x < $uCount) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                            }
                            $fInfo['username'] = $uNames;
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_liked_store', $fInfo);
                        }
                    }
                    if (count($filteredDesignerItems)) {
                        foreach ($filteredDesignerItems as $item_id => $users) {
                            $uInfo = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $users)->get();
                            $uNames = [];
                            $fInfo = [
                                'username' => "",
                                'other' => 0,
                            ];
                            $uCount = $uInfo->count();
                            if ($uCount > 3) {
                                $x = 0;
                                while ($x < 3) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                                $fInfo['other'] = $uCount - 3;
                            } else {
                                $x = 0;
                                while ($x < $uCount) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                            }
                            $fInfo['username'] = $uNames;
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_liked_designer', $fInfo);
                        }
                    }
                }
            } catch (Exception $ex) {
            }

            // shared items
            try {
                if (!$friends) {
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                }

                $sharedItems = \OlaHub\UserPortal\Models\SharedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($friends, $myGroups) {
                        $q->where(function ($query) use ($friends) {
                            $query->whereIn('user_id', $friends);
                        });
                        $q->orWhere(function ($query) use ($myGroups) {
                            $query->whereIn('group_id', $myGroups);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($sharedItems->count()) {
                    $filteredStoreItems = [];
                    $filteredDesignerItems = [];
                    foreach ($sharedItems as $litem) {
                        if ($litem->item_type == 'store') {
                            if (!isset($filteredStoreItems[$litem->item_id])) {
                                $filteredStoreItems[$litem->item_id] = [];
                            }

                            array_push($filteredStoreItems[$litem->item_id], $litem->user_id);
                        } else {
                            if (!isset($filteredDesignerItems[$litem->item_id])) {
                                $filteredDesignerItems[$litem->item_id] = [];
                            }

                            array_push($filteredDesignerItems[$litem->item_id], $litem->user_id);
                        }
                    }
                    if (count($filteredStoreItems)) {
                        foreach ($filteredStoreItems as $item_id => $users) {
                            $uInfo = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $users)->get();
                            $uNames = [];
                            $fInfo = [
                                'username' => "",
                                'other' => 0,
                            ];
                            $uCount = $uInfo->count();
                            if ($uCount > 3) {
                                $x = 0;
                                while ($x < 3) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                                $fInfo['other'] = $uCount - 3;
                            } else {
                                $x = 0;
                                while ($x < $uCount) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                            }
                            $fInfo['username'] = $uNames;
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_shared_store', $fInfo);
                        }
                    }
                    if (count($filteredDesignerItems)) {
                        foreach ($filteredDesignerItems as $item_id => $users) {
                            $uInfo = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $users)->get();
                            $uNames = [];
                            $fInfo = [
                                'username' => "",
                                'other' => 0,
                            ];
                            $uCount = $uInfo->count();
                            if ($uCount > 3) {
                                $x = 0;
                                while ($x < 3) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                                $fInfo['other'] = $uCount - 3;
                            } else {
                                $x = 0;
                                while ($x < $uCount) {
                                    $uNames[] = $uInfo[$x]->first_name;
                                    $x++;
                                }
                            }
                            $fInfo['username'] = $uNames;
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_shared_designer', $fInfo);
                        }
                    }
                }
            } catch (Exception $ex) {
            }

            // merchants

            // Category items
            $followedCategory = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 3)
                ->select('catalog_item_categories.id')
                ->join('catalog_item_categories', 'catalog_item_categories.parent_id', 'following.target_id')->get();
            $categoryIds = [];
            foreach ($followedCategory as $followedCategoryID) {
                $categoryIds[] = $followedCategoryID->id;
            }

            $cItems = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('parent_item_id');
                $query->orWhere('parent_item_id', '0');
            })->whereRaw($month)->inRandomOrder()->whereIN('category_id', $categoryIds)->paginate(10);
            $itemsCategory = [];
            foreach ($cItems as $item) {
                if (!isset($itemsCategory[$item->category_id])) {
                    $itemsCategory[$item->category_id] = [];
                }

                array_push($itemsCategory[$item->category_id], $item);
            }

            foreach ($itemsCategory as $m => $im) {
                if (count($im) == 1) {
                    if (is_object($im)) {
                        $timeline[] = $this->handlePostTimeline($im, 'item_category');
                    }
                } else {

                    $timeline[] = $this->handlePostTimeline($im, 'category_multi_item');
                }
            }
            // occasion items
            $followedOccasion = \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('type', 4)->get();
            $occasionIds = [];
            foreach ($followedOccasion as $followedOccasionID) {
                $occasionIds[] = $followedOccasionID->target_id;
            }

            $oItems = \OlaHub\UserPortal\Models\CatalogItem::join('catalog_item_occasions', 'catalog_item_occasions.item_id', 'catalog_items.id')->select('catalog_item_occasions.occasion_id', 'catalog_items.*')->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('catalog_items.parent_item_id');
                $query->orWhere('catalog_items.parent_item_id', '0');
            })
                ->whereRaw($monthC)->inRandomOrder()
                ->whereIn('occasion_id', $occasionIds)
                ->paginate(10);
            $itemsOccasion = [];
            foreach ($oItems as $item) {
                if (!isset($itemsOccasion[$item->occasion_id])) {
                    $itemsOccasion[$item->occasion_id] = [];
                }

                array_push($itemsOccasion[$item->occasion_id], $item);
            }

            foreach ($itemsOccasion as $m => $im) {
                if (count($im) == 1) {
                    if (is_object($im)) {
                        $timeline[] = $this->handlePostTimeline($im, 'occasion_item');
                    }
                } else {

                    $timeline[] = $this->handlePostTimeline($im, 'occasion_multi_item');
                }
            }
            // intrest items
            $getfollowedInterests = DB::table('users')->where("id", app('session')->get('tempID'))->select('interests')->get();
            $followedInterests = explode(',', $getfollowedInterests[0]->interests);

            foreach ($followedOccasion as $followedOccasionID) {
                $occasionIds[] = $followedOccasionID->target_id;
            }

            $interestsItems = \OlaHub\UserPortal\Models\CatalogItem::join('catalog_item_interests', 'catalog_item_interests.item_id', 'catalog_items.id')->select('catalog_item_interests.interest_id', 'catalog_items.*')->whereHas('quantityData', function ($q) {
                $q->where('quantity', '>', 0);
            })->where(function ($query) {
                $query->whereNull('catalog_items.parent_item_id');
                $query->orWhere('catalog_items.parent_item_id', '0');
            })->whereRaw($monthC)->inRandomOrder()
                ->whereIn('interest_id', $followedInterests)

                ->paginate(10);
            // $timeline[] = $this->handlePostTimeline($interestsItems, 'intrests_multi_item');
            $itemsInterests = [];
            foreach ($interestsItems as $item) {
                if (!isset($itemsInterests[$item->interest_id])) {
                    $itemsInterests[$item->interest_id] = [];
                }

                array_push($itemsInterests[$item->interest_id], $item);
            }

            foreach ($itemsInterests as $m => $im) {
                if (count($im) == 1) {
                    if (is_object($im)) {
                        $timeline[] = $this->handlePostTimeline($im, 'intrests_item');
                    }
                } else {

                    $timeline[] = $this->handlePostTimeline($im, 'intrests_multi_item');
                }
            }

            // designer items
            $dItems = \OlaHub\UserPortal\Models\DesignerItems::where(function ($query) {
                $query->whereNull('parent_item_id');
                $query->orWhere('parent_item_id', '0');
            })->where('item_stock', '>', 0)->whereRaw($month)->inRandomOrder()->paginate(20);
            $itemsDesigners = [];
            foreach ($dItems as $item) {
                if (!isset($itemsDesigners[$item->designer_id])) {
                    $itemsDesigners[$item->designer_id] = [];
                }

                array_push($itemsDesigners[$item->designer_id], $item);
            }
            foreach ($itemsDesigners as $d => $id) {
                if (count($id) == 1) {
                    if (is_object($id)) {
                        $timeline[] = $this->handlePostTimeline($id, 'designer_item');
                    }
                } else {
                    $timeline[] = $this->handlePostTimeline($id, 'designer_multi_item');
                }
            }
        }

        // merchants
        $merchants = \OlaHub\UserPortal\Models\Brand::whereRaw($month)->orderBy('created_at', 'desc')->paginate(20);
        foreach ($merchants as $merchant) {
            $timeline[] = $this->handlePostTimeline($merchant, 'merchant');
        }
        // designers
        $designers = \OlaHub\UserPortal\Models\Designer::whereHas("itemsMainData")
            ->whereRaw($month)->orderBy('created_at', 'desc')->paginate(20);
        // $designers = \OlaHub\UserPortal\Models\Designer::whereRaw($month)->orderBy('created_at', 'desc')->paginate(20);
        foreach ($designers as $designer) {
            $timeline[] = $this->handlePostTimeline($designer, 'designer');
        }

        // brand items
        $bItems = \OlaHub\UserPortal\Models\CatalogItem::whereHas('quantityData', function ($q) {
            $q->where('quantity', '>', 0);
        })->where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        })->whereRaw($month)->inRandomOrder()->paginate(30);
        $itemsBrands = [];
        foreach ($bItems as $item) {
            if (!isset($itemsBrands[$item->store_id])) {
                $itemsBrands[$item->store_id] = [];
            }

            array_push($itemsBrands[$item->store_id], $item);
        }
        foreach ($itemsBrands as $m => $im) {
            if (count($im) == 1) {
                if (is_object($im)) {
                    $timeline[] = $this->handlePostTimeline($im, 'item');
                }
            } else {
                $timeline[] = $this->handlePostTimeline($im, 'multi_item');
            }
        }

        // Sponsors
        $sponsors_arr = [];
        try {
            $timelinePosts = \DB::table('campaign_slot_prices')->where('country_id', app('session')->get('def_country')->id)->where('is_post', '1')->get();
            if ($timelinePosts->count() > 0) {
                foreach ($timelinePosts as $onePost) {
                    $sponsors = \OlaHub\Models\AdsMongo::where('slot', $onePost->id)->where('country', app('session')->get('def_country')->id)->orderBy('id', 'RAND()')->paginate(5);
                    foreach ($sponsors as $one) {
                        $campaign = \OlaHub\Models\Ads::where('campign_token', $one->token)->first();
                        $liked = 0;
                        if ($campaign) {
                            $oldLike = \OlaHub\UserPortal\Models\UserPoints::where('user_id', app('session')->get('tempID'))
                                ->where('country_id', app('session')->get('def_country')->id)
                                ->where('campign_id', $campaign->id)
                                ->first();
                            if ($oldLike) {
                                $liked = 1;
                            }
                        }
                        $sponsors_arr[] = [
                            'type' => 'sponser',
                            "adToken" => isset($one->token) ? $one->token : null,
                            'updated_at' => isset($one->updated_at) ? $one->updated_at : 0,
                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($one->created_at),
                            'post' => isset($one->_id) ? $one->_id : 0,
                            "adSlot" => isset($one->slot) ? $one->slot : 0,
                            "adRef" => isset($one->content_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->content_ref) : null,
                            "adText" => isset($one->content_text) ? $one->content_text : null,
                            "adLink" => isset($one->access_link) ? $one->access_link : null,
                            "liked" => $liked,
                        ];
                    }
                }
            }
        } catch (Exception $ex) {
        }

        // communities
        $communities_arr = [];
        try {
            $communities = \OlaHub\UserPortal\Models\groups::where('olahub_community', 1)
                ->whereIn("countries", [app("session")->get("def_country")->id])->paginate(3);
            if ($communities->count() > 0) {
                foreach ($communities as $one) {
                    $communities_arr[] = [
                        'type' => 'community',
                        "slug" => isset($one->slug) ? $one->slug : "",
                        "image" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->image, "community"),
                        "cover" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($one->cover, "community"),
                        "name" => isset($one->name) ? $one->name : "",
                        "desc" => isset($one->description) ? $one->description : null,
                    ];
                }
            }
        } catch (Exception $ex) {
        }

        if (count($timeline) > 0) {
            shuffle($timeline);
            $count_timeline = count($timeline);
            $breakSponsor = count($sponsors_arr) > 1 ? (int) @($count_timeline / count($sponsors_arr) / 2) : 0;
            $breakCommunity = count($communities_arr) > 1 ? (int) @($count_timeline / count($communities_arr) / 2) : 0;
            $startSponsor = 0;
            $startCommunity = 0;
            for ($i = 0; $i < count($timeline); $i++) {
                $all[] = $timeline[$i];
                if ($breakSponsor - 1 == $i && @$sponsors_arr[$startSponsor]) {
                    $all[] = $sponsors_arr[$startSponsor];
                    $startSponsor++;
                    $breakSponsor = $breakSponsor * 2;
                }
                if ($breakCommunity - 1 == $i && @$communities_arr[$startCommunity]) {
                    $all[] = $communities_arr[$startCommunity];
                    $startCommunity++;
                    $breakCommunity = $breakCommunity * 2;
                }
            }

            $return['data'] = $all;
        }
        if ($page == 1) {
            $return['celebrations'] = $celebrations;
            $return['upcoming'] = $upcoming;
        }
        // dd($return)
        return response(array $return, 200);
    }

    private function handlePostTimeline($data, $type, $fInfo = null)
    {
        $liked = 0;
        $likerData = [];
        $return = [
            'type' => $type,
            'total_share_count' => 0,
            'shares_count' => 0,
            'likers_count' => 0,
            'liked' => $liked,
            'likersData' => $likerData,
            'time' => isset($data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($data->created_at) : null,
            'user_info' => $fInfo ? $fInfo : $this->userInfo,
        ];
        switch ($type) {
            case 'item_liked_store':
            case 'item_shared_store':
                $brand = $data->brand;
                $images = $data->images;
                $return['type'] = $type == 'item_liked_store' ? 'item_liked' : 'item_shared';
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : null,
                    'merchant_title' => isset($brand->name) ? $brand->name : null,
                ];
                break;
            case 'item_liked_designer':
            case 'item_shared_designer':
                $designer = $data->designer;
                $images = $data->images;
                $return['type'] = $type == 'item_liked_designer' ? 'item_liked' : 'item_shared';
                $return['target'] = 'designer';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : null,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : null,
                ];
                break;
            case 'item':
                $brand = $data->brand;
                $images = $data->images;
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : null,
                    'merchant_title' => isset($brand->name) ? $brand->name : null,
                ];
                break;

            case 'occasion_item':

                $occasion = DB::table('occasion_types')->where('id', $data[0]['occasion_id'])->get();
                $name = isset($occasion[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasion[0], 'name') : null;

                $images = $data->images;
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'occasion',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion[0]->logo_ref),

                    'merchant_slug' => isset($occasion[0]->occasion_slug) ? $occasion[0]->occasion_slug : null,
                    'merchant_title' => $name,
                ];
                break;
            case 'intrests_item':

                $interest = DB::table('lkp_interests')->where('id', $data[0]['interest_id'])->get();
                $name = isset($interest[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest[0], 'name') : null;

                $images = $data->images;
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'interest',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest[0]->image_ref),

                    'merchant_slug' => isset($interest[0]->interest_slug) ? $interest[0]->interest_slug : null,
                    'merchant_title' => $name,
                ];
                break;
            case 'item_category':

                $subcategory = $data->category;

                $category = DB::table('catalog_item_categories')->where('id', $subcategory['parent_id'])->get();
                $name = isset($category[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category[0], 'name') : null;

                $images = $data->images;
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'category',
                    'merchant_slug' => isset($category[0]->category_slug) ? $category[0]->category_slug : null,
                    'merchant_title' => $name,
                ];
                break;
            case 'designer_item':
                $designer = $data->designer;
                $images = $data->images;
                $return['target'] = 'designer';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : null;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : null,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : null,
                ];
                break;
            case 'merchant':
                $return['merchant_title'] = $data->name;
                $return['merchant_slug'] = isset($data->store_slug) ? $data->store_slug : null;
                $return['avatar_url'] = isset($data->image_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($data->image_ref) : null;
                break;
            case 'designer':
                $return['merchant_title'] = $data->brand_name;
                $return['merchant_slug'] = isset($data->designer_slug) ? $data->designer_slug : null;
                $return['avatar_url'] = isset($data->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($data->logo_ref) : null;
                break;
            case 'multi_item':
                $items = [];
                $brand = $data[0]->brand;
                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : null,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null,
                        'item_title' => $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : null,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : null,
                    'merchant_title' => isset($brand->name) ? $brand->name : null,
                ];
                break;
            case 'category_multi_item':

                $items = [];
                $subcategory = $data[0]->category;
                $category = DB::table('catalog_item_categories')->where('id', $subcategory['parent_id'])->get();
                $name = isset($category[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category[0], 'name') : null;

                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : null,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null,
                        'item_title' => $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : null,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'category',
                    'merchant_slug' => isset($category[0]->category_slug) ? $category[0]->category_slug : null,
                    'merchant_title' => $name,
                ];

                break;
            case 'occasion_multi_item':
                $items = [];

                $occasion = DB::table('occasion_types')->where('id', $data[0]['occasion_id'])->get();
                $name = isset($occasion[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasion[0], 'name') : null;

                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : null,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null,
                        'item_title' => $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : null,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'occasion',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion[0]->logo_ref),

                    'merchant_slug' => isset($occasion[0]->occasion_slug) ? $occasion[0]->occasion_slug : null,
                    'merchant_title' => $name,
                ];

                break;
            case 'intrests_multi_item':
                $interest = DB::table('lkp_interests')->where('id', $data[0]['interest_id'])->get();
                $items = [];

                $name = isset($interest[0]->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest[0], 'name') : null;

                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : null,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null,
                        'item_title' => $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : null,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'intrests',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest[0]->image_ref),

                    'merchant_slug' => isset($interest[0]->interest_slug) ? $interest[0]->interest_slug : null,
                    'merchant_title' => $name,
                ];
                break;
            case 'designer_multi_item':
                $items = [];
                $designer = $data[0]->designer;
                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : null,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : null,
                        'item_title' => $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : null,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : null,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : null,
                ];
                break;
        }
        return $return;
    }

    private function getMerchantIndexFromSlug($timeline, $storeSlug)
    {
        $index = false;
        if (is_array($timeline) && count($timeline) > 0) {
            foreach ($timeline as $key => $value) {
                if (isset($value["merchant_info"]) && isset($value["merchant_info"]["merchant_slug"]) && $value["merchant_info"]["merchant_slug"] == $storeSlug) {
                    $index = $key;
                    break;
                }
            }
        }

        return $index;
    }

    public function shareNewItem()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "shareNewItem"]);

        if (isset($this->requestShareData['itemID']) && !$this->requestShareData['itemID']) {
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start share new item"]);
        $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $this->requestShareData['itemID'])->first();
        if ($item) {
            $post = (new \OlaHub\UserPortal\Helpers\ItemHelper)->createItemPost($item->item_slug);

            if (in_array(app('session')->get('tempID'), $post->shares)) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'shareBefore', 'code' => 204]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => false, 'msg' => 'shareBefore', 'code' => 204], 200);
            }

            $post->push('shares', app('session')->get('tempID'), true);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => true, 'msg' => 'shareItem', 'code' => 200]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => true, 'msg' => 'shareItem', 'code' => 200], 200);
        }
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => false, 'msg' => 'NoData', 'code' => 204]]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End share new item"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function userFollow($type, $id)
    {
        $following = (new \OlaHub\UserPortal\Models\Following);
        $following->target_id = $id;
        $following->user_id = app('session')->get('tempID');
        if ($type == 'brands') {
            $following->type = 1;
        } else if ($type == 'category') {
            $following->type = 3;
        } else if ($type == 'occasion') {
            $following->type = 4;
        } else  $following->type = 2;
        $following->save();
        return response(['status' => true, 'msg' => 'follow successfully', 'code' => 200], 200);
    }

    public function userUnFollow($type, $id)
    {
        if ($type == 'brands') {
            $typeNum = 1;
        } else if ($type == 'category') {
            $typeNum = 3;
        } else if ($type == 'occasion') {
            $typeNum = 4;
        } else $typeNum = 2;

        \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $id)
            ->where('type', $typeNum)->delete();
        return response(['status' => true, 'msg' => 'unfollow successfully', 'code' => 200], 200);
    }

    public function listUserFollowing()
    {
        $return = [];
        $brands = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 1)->get();
        $designers = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 2)->get();
        $categories = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 3)->get();
        $occasions = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 4)->get();
        if (isset($brands)) {
            $brands = \OlaHub\UserPortal\Models\Brand::whereIn('id', $brands)->get();
            foreach ($brands as $brand) {
                $return['brands']['data'][] = [
                    "brandID" => isset($brand->id) ? $brand->id : 0,
                    'brandName' => isset($brand->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, "name") : null,
                    'brandLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'brandSlug' => isset($brand->store_slug) ? $brand->store_slug : null,
                ];
            }
        }
        if (isset($categories)) {
            $categoryS = DB::table('catalog_item_categories')->whereIn('id', $categories)->get();
            foreach ($categoryS as $category) {
                $return['categories']['data'][] = [
                    "categoryID" => isset($category->id) ? $category->id : 0,
                    'categoryName' => isset($category->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($category, "name") : null,
                    'categorySlug' => isset($category->category_slug) ? $category->category_slug : null,
                ];
            }
        }
        if (isset($occasions)) {
            $occasionS = DB::table('occasion_types')->whereIn('id', $occasions)->get();
            foreach ($occasionS as $occasion) {
                $return['occasions']['data'][] = [
                    "occasionID" => isset($occasion->id) ? $occasion->id : 0,
                    'occasionName' => isset($occasion->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($occasion, "name") : null,
                    'occasionLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($occasion->logo_ref),
                    'occasionSlug' => isset($occasion->occasion_slug) ? $occasion->occasion_slug : null,
                ];
            }
        }

        if (isset($designers)) {
            $designers = \OlaHub\UserPortal\Models\Designer::whereIn('id', $designers)->get();
            foreach ($designers as $designer) {
                $return['designer'][] = [
                    "designerId" => isset($designer->id) ? $designer->id : 0,
                    'designerName' => isset($designer->brand_name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, "brand_name") : null,
                    'designerLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'designerSlug' => isset($designer->designer_slug) ? $designer->designer_slug : null,
                ];
            }
        }
        return response(['status' => true, 'data' => $return, 'code' => 200], 200);
    }

    public function getFriendsToMention($friendNameToFind)
    {
        $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
        if (count($friends) > 0) {
            $friends = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $friends)->Where('first_name', 'like', '%' . $friendNameToFind . '%')->take(5)->get();
            foreach ($friends as $friend) {
                $return['data'][] = [
                    "profile" => $friend->id,
                    "name" => ucwords($friend->first_name) . ' ' . ucwords($friend->last_name),
                    "profile_url" => $friend->profile_url,
                    "user_gender" => isset($friend->user_gender) ? $friend->user_gender : null,
                    "avatar" => isset($friend->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->profile_picture),
                    "cover_photo" => isset($friend->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->cover_photo),
                ];
            }
        }
        if (count($friends) > 0) {
            $return['status'] = true;
            $return['code'] = 200;
            return response($return, 200);
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }


    public function getSuggestFriends()
    {

        $suggestedBefore = ($this->requestData->suggestedBefore);
        $friendsOfFrinendsIds = [];
        $mutualFriend = [];
        $suggestFriends = [];

        $suggestedFriendsIds = [];
        $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
        // dd($friends);
        $requestedFriends = \OlaHub\UserPortal\Models\Friends::getAllSentRequest(app('session')->get('tempID'));
        $blocked = \OlaHub\UserPortal\Models\Friends::getAllblocked(app('session')->get('tempID'));
        if (count($friends) > 0) {
            $friendsOfFrinends = \OlaHub\UserPortal\Models\Friends::whereIn('friend_id', $friends)->orWhereIn('user_id', $friends);
            if (count($suggestedBefore) > 0) {
                $friendsOfFrinends =  $friendsOfFrinends
                    ->whereNotIn('friend_id', $suggestedBefore)
                    ->whereNotIn('user_id', $suggestedBefore);
            }
            $friendsOfFrinends = $friendsOfFrinends

                ->where('friend_id', '!=', app('session')->get('tempID'))
                ->where('user_id', '!=', app('session')->get('tempID'))
                ->where('status', 1)
                ->whereNotIn('friend_id', $requestedFriends)
                ->whereNotIn('user_id', $requestedFriends)
                ->whereNotIn('friend_id', $blocked)
                ->whereNotIn('user_id', $blocked)
                ->groupBy('friend_id')
                ->groupBy('user_id')
                ->limit(30)
                ->get();
            foreach ($friendsOfFrinends as $id) {
                if (
                    in_array($id->user_id, $suggestedBefore) 
                    || 
                    in_array($id->friend_id, $suggestedBefore)
                    ||
                    in_array($id->user_id, $requestedFriends) 
                    || 
                    in_array($id->friend_id, $requestedFriends)
                    ||
                    (in_array($id->user_id, $friends) &&in_array($id->friend_id, $friends))
                    || $id->friend_id == app('session')->get('tempID') || $id->user_id == app('session')->get('tempID')
                ) {
                } else {
                 
                    if (in_array($id->user_id, $friends)) {
                        $suggesstFriends = (\OlaHub\UserPortal\Models\Friends::getFriendsList($id->friend_id));
                        $mutualFriends = count(array_intersect($suggesstFriends, $friends));
                        $friendsOfFrinendsIds[] = $id->friend_id;
                        $mutualFriend[$id->friend_id] = $mutualFriends;
                    } else {
                        $suggesstFriends = (\OlaHub\UserPortal\Models\Friends::getFriendsList($id->user_id));
                        $mutualFriends = count(array_intersect($suggesstFriends, $friends));
                        $friendsOfFrinendsIds[] = $id->user_id;
                        $mutualFriend[$id->user_id] =  $mutualFriends;
                    }
                }
            }

            $friendsOfFriendUsers = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $friendsOfFrinendsIds)->inRandomOrder()->get();

            foreach ($friendsOfFriendUsers as $suggest) {
                $suggestedFriendsIds[] = $suggest->id;
                $suggestFriends[] = [
                    'type' => 'friendsOfFriend',
                    "status" => 1,
                    "profile" => $suggest->id,
                    "mutualFriend" => $mutualFriend[$suggest->id],
                    "name" => ucwords($suggest->first_name)  . ' ' . ucwords($suggest->last_name),
                    "profile_url" => $suggest->profile_url,
                    "user_gender" => isset($suggest->user_gender) ? $suggest->user_gender : NULL,
                    "avatar" => isset($suggest->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture),
                    "cover_photo" => isset($suggest->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo),
                ];
            }
        }
        if (count($suggestFriends) < 30) {

            $userGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr((app('session')->get('tempID')));

            $userGroupsCommonMember = \OlaHub\UserPortal\Models\GroupMembers::getMembersOfCommonGroups($userGroups);

            $groupsMembers = \OlaHub\UserPortal\Models\UserModel::whereIn('id', $userGroupsCommonMember)
                ->whereNotIn('id', $requestedFriends)
                ->whereNotIn('id', $blocked)
                ->whereNotIn('id', $friends)
                ->whereNotIn('id', $suggestedBefore)
                ->inRandomOrder()
                ->groupBy('id')
                ->where('id', '!=', app('session')->get('tempID'))
                ->limit(30 - count($suggestFriends))
                ->get();
            foreach ($groupsMembers as $suggest) {
                $suggesstGroups = (\OlaHub\UserPortal\Models\GroupMembers::getGroupsArr($suggest->id));

                $mutualGroups = count(array_intersect($suggesstGroups, $userGroups));

                $suggestedFriendsIds[] = $suggest->id;
                $suggestFriends[] = [
                    'id' => $suggest->id,
                    'type' => 'groups',
                    "status" => 1,
                    "profile" => $suggest->id,
                    "mutualFriend" => $mutualGroups,
                    "name" => ucwords($suggest->first_name)  . ' ' . ucwords($suggest->last_name),
                    "profile_url" => $suggest->profile_url,
                    "user_gender" => isset($suggest->user_gender) ? $suggest->user_gender : NULL,
                    "avatar" => isset($suggest->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture),
                    "cover_photo" => isset($suggest->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo),
                ];
            }
        }
        if (count($suggestFriends) < 30) {

            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            $userIntreast = explode(",", $user->interests);
            $fq = [];
            foreach ($userIntreast as $i)
                $fq[] = "FIND_IN_SET($i, interests)";


            $mathedIntreast = \OlaHub\UserPortal\Models\UserModel::whereNotIn('id', $suggestedBefore)
                ->whereNotIn('id', $suggestedBefore)
                ->whereNotIn('id', $friends)
                ->inRandomOrder()
                ->whereRaw($fq[0])
                ->where('id', '!=', app('session')->get('tempID'))
                ->groupBy('id')
                ->limit(30 - count($suggestFriends))
                ->get();


            foreach ($mathedIntreast as $suggest) {
                $suggestedFriendsIds[] = $suggest->id;
                $suggestFriends[] = [
                    'type' => 'byIntreasts',
                    "status" => 1,
                    "profile" => $suggest->id,
                    "mutualFriend" => 0,
                    "name" => ucwords($suggest->first_name)  . ' ' . ucwords($suggest->last_name),
                    "profile_url" => $suggest->profile_url,
                    "user_gender" => isset($suggest->user_gender) ? $suggest->user_gender : NULL,
                    "avatar" => isset($suggest->profile_picture) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture),
                    "cover_photo" => isset($suggest->cover_photo) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->cover_photo),
                ];
            }
        }
        if (count($suggestFriends) > 0) {

            $return['SuggestED'] = $suggestedFriendsIds;
            $return['data'] = $suggestFriends;
            $return['status'] = TRUE;
            $return['code'] = 200;
            return response($return, 200);
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }

    public function getSuggestGroups()
    {
        $userGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr((app('session')->get('tempID')));
        $suggestGroup = [];
        $suggestedBefore = ($this->requestData->suggestedBefore);
        $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList(app('session')->get('tempID'));
        if (count($friends) > 0) {
            $friendsGroupsIds = \OlaHub\UserPortal\Models\GroupMembers::getFriendsGroups($friends, $suggestedBefore);
        }
        if (count($friendsGroupsIds) > 0) {
            $groupsData = \OlaHub\UserPortal\Models\groups::whereIn('id', $friendsGroupsIds)
                ->where('privacy', '!=', 1)
                ->inRandomOrder()
                ->whereNotIn('id', $userGroups)
                ->whereNotIn('id', $suggestedBefore)

                ->limit(30)
                ->get();

            foreach ($groupsData as $suggest) {
                $groupMembers = \OlaHub\UserPortal\Models\GroupMembers::getMembersArr($suggest->id);

                $friendsInGroup = count(array_intersect($groupMembers, $friends));

                $suggestedFriendsIds[] = $suggest->id;
                $suggestGroup[] = [
                    "status" => 1,
                    'type' => 'friends',
                    'privacy' => $suggest->privacy,
                    "id" => $suggest->id,
                    "friends" => $friendsInGroup,
                    "name" => $suggest->name,
                    "image" => isset($suggest->image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->image) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture),
                ];
            }
        }

        if (count($suggestGroup) < 30) {
            $user = \OlaHub\UserPortal\Models\UserModel::where('id', app('session')->get('tempID'))->first();
            $userIntreast = explode(",", $user->interests);
            $fq = [];
            foreach ($userIntreast as $i)
                $fq[] = "FIND_IN_SET($i, interests)";

            $groupsData = \OlaHub\UserPortal\Models\groups::where('privacy', '!=', 1)
                ->inRandomOrder()
                ->whereNotIn('id', $suggestedBefore)

                ->whereNotIn('id', $userGroups)
                ->whereRaw($fq[0])

                ->limit(30)
                ->get();


            foreach ($groupsData as $suggest) {

                $suggestedFriendsIds[] = $suggest->id;
                $suggestGroup[] = [
                    "status" => 1,
                    'type' => 'byIntreasts',
                    "id" => $suggest->id,
                    'privacy' => $suggest->privacy,
                    "name" => $suggest->name,
                    "image" => isset($suggest->image) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->image) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($suggest->profile_picture),
                ];
            }
        }
        if (count($suggestGroup) > 0) {

            $return['SuggestED'] = $suggestedFriendsIds;
            $return['data'] = $suggestGroup;
            $return['status'] = TRUE;
            $return['code'] = 200;
            return response($return, 200);
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
