<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use \OlaHub\UserPortal\Models\Post;

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
        $this->userInfo = NULL;
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
            throw new NotAcceptableHttpException(404);
        }
        $return['countries'] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($countries, '\OlaHub\UserPortal\ResponseHandlers\CountriesForPrequestFormsResponseHandler');
        $allCountries = \OlaHub\UserPortal\Models\ShippingCountries::selectRaw("countries.name as text, countries.id as value, phonecode, LOWER(code) as flag, LOWER(code) as code")
            ->join('countries', 'countries.id', 'shipping_countries.olahub_country_id')
            ->orderBy('shipping_countries.name', 'asc')->get();
        foreach ($allCountries as $country) {
            $country->text = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($country, 'text');
        }
        $return['allCountries'] = $allCountries;
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
                "socialType" => isset($one->second_type) ? $one->second_type : NULL,
                "socialTitle" => isset($one->content_text) ? $one->content_text : NULL,
                "socialLink" => isset($one->content_link) ? $one->content_link : NULL,
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
            "content" => $page->content_text
        ];
        $return['data'] = $pageData;
        $return['status'] = true;
        $return['code'] = 200;
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $return]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch static pages"]);
        (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
        return response($return, 200);
    }

    public function getUserNotification()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Get user notification"]);

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start fetch user notification"]);
        $notification = \OlaHub\UserPortal\Models\Notifications::with('userData')->where('user_id', (int) app('session')->get('tempID'))->orderBy("created_at", "DESC")->get();

        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check notification existance"]);
        if ($notification->count() > 0) {
            $allNotifications = [];
            foreach ($notification as $one) {
                $userData = $one["userData"][0];
                $groupData = @$one["groupData"][0];
                $celebrationData = @$one["celebrationData"][0];
                $allNotifications[] = [
                    "id" => $one->id,
                    "type" => $one->type,
                    "content" => $one->content,
                    "celebration_id" => $one->celebration_id,
                    "post_id" => $one->post_id,
                    "group_id" => $one->group_id,
                    "user_name" => $userData["first_name"] . " " . $userData["last_name"],
                    "community_title" => @$groupData["name"],
                    "celebration_title" => @$celebrationData["title"],
                    "profile_url" => $userData["profile_url"],
                    "avatar_url" => isset($userData["profile_picture"]) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData["profile_picture"]) : NULL,
                    "read" => $one->read,
                    "for_user" => $one->user_id,
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => $notification]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End check notification existance"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch user notification"]);
            return $allNotifications;
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
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Read notification"]);

        if (isset($this->requestData->notificationId) && $this->requestData->notificationId) {
            if ($this->requestData->notificationId == 'all') {
                \OlaHub\UserPortal\Models\Notifications::where('user_id', app('session')->get('tempID'))->update(['read' => 1]);
                return ['status' => true, 'msg' => 'Notifications has been read', 'code' => 200];
            } else {
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
            'code' => 200
        ], 200);
    }

    public function checkUserMerchant()
    {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['module_name' => "General", 'function_name' => "Check user merchant"]);

        $data = [
            "isMerchantUser" => false,
            "isStoreUser" => false
        ];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start check user merchant"]);
        if (app('session')->get('tempData')->for_merchant) {
            $data = [
                "isMerchantUser" => true
            ];
        }
        if (app('session')->get('tempData')->for_store) {
            $data = [
                "isStoreUser" => true
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
        if (isset($this->requestFilter->word) && strlen($this->requestFilter->word) > 2 /* && strlen($this->requestFilter->word) % 3 == 0 */) {
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
            if ($users->count() > 0) {
                $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::handlingResponseCollection($users, '\OlaHub\UserPortal\ResponseHandlers\searchUsersForPrequestFormsResponseHandler');
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
                where (LOWER(`email`) like '%" . $q . "%' or mobile_no like '%" . $q . "%' 
                and LOWER(`first_name`) sounds like '" . $q . "'
                and LOWER(`last_name`) sounds like '" . $q . "')  
                and id <> " . app('session')->get('tempID') . " and is_active = 1";

                // groups
                $searchQuery[] = "select count(id) as search from groups 
                where LOWER(`name`) sounds like '" . $q . "'
                or LOWER(`description`) sounds like '" . $q . "'";
            }
            $handle = \DB::select(\DB::raw(implode(' union all ', $searchQuery)));
            // brands
            if ($handle[0]->search > 0) {
                $searchData[] = [
                    "type" => "brands"
                ];
            }
            // designers
            if ($handle[1]->search > 0) {
                $searchData[] = [
                    "type" => "designers"
                ];
            }
            // items
            if ($handle[2]->search > 0) {
                $searchData[] = [
                    "type" => "items"
                ];
            }
            // designer items
            if ($handle[3]->search > 0) {
                $searchData[] = [
                    "type" => "desginer_items"
                ];
            }
            if (app('session')->get('tempID')) {
                // users
                if ($handle[4]->search > 0) {
                    $searchData[] = [
                        "type" => "users"
                    ];
                }
                // groups
                if ($handle[5]->search > 0) {
                    $searchData[] = [
                        "type" => "groups"
                    ];
                }
            }

            $ditems = [];
            $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, 5);
            if ($items) {
                $ditems["items"] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ItemSearchResponseHandler')['data'];
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
            'code' => 200
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
        $count = 18;
        $searchData = [];
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Start search according filter"]);
        if ((isset($this->requestFilter->word) && strlen($this->requestFilter->word) > 1) && isset($this->requestFilter->type) && strlen($this->requestFilter->type) > 1) {
            $q = mb_strtolower($this->requestFilter->word);
            $type = $this->requestFilter->type;

            switch ($type) {
                case "users":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search users filter"]);
                    if (app('session')->get('tempID')) {
                        $users = \OlaHub\UserPortal\Models\UserModel::searchUsers($q, false, false, $count, TRUE);
                        if ($users->count() > 0) {
                            $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($users, '\OlaHub\UserPortal\ResponseHandlers\UserSearchResponseHandler');
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
                    $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, $count);
                    if ($items->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ItemSearchResponseHandler');
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
        if (!empty($this->requestData->userPhoneNumber))
            $this->requestData->userPhoneNumber = (new \OlaHub\UserPortal\Helpers\UserHelper)->fullPhone($this->requestData->userPhoneNumber);
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
        $page = $request->input('page');
        $now = date('Y-m-d');
        $month = 'created_at BETWEEN DATE_ADD(CURRENT_DATE(), INTERVAL -30 DAY) AND CURRENT_DATE()';
        $timeline = [];
        $friends = NULL;
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
            if (!$page) {
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
                        'username' => "$gift_sender->first_name $gift_sender->last_name"
                    );
                    $items = \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $gift->id)->get();
                    $nonSeenGiftsResponse = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');
                    $all[] = [
                        'type' => 'gift',
                        'gift_sender' => $gift_sender,
                        'message' => isset($gift->gift_message) ? $gift->gift_message : "",
                        'video' => isset($gift->gift_video_ref) ? $gift->gift_video_ref : "",
                        'items' => $nonSeenGiftsResponse['data']
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
                                            "content" => isset($celebrationContent->reference) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($celebrationContent->reference) : NULL,
                                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($celebrationContent->created_at),
                                            'user_info' => [
                                                'user_id' => $author->id,
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                                'username' => $authorName,
                                            ]
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
                if (!$friends)
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                $myGroups = \OlaHub\UserPortal\Models\GroupMembers::getGroupsArr($user->id);
                $posts = Post::where(function ($q) use ($friends, $myGroups) {
                    $q->where(function ($userPost) use ($friends) {
                        $userPost->whereIn('user_id', $friends);
                        $userPost->where('friend_id', NULL);
                    });
                    $q->orWhere(function ($userPost) {
                        $userPost->where('friend_id', app('session')->get('tempID'));
                    });
                    $q->orWhere(function ($userPost) use ($friends, $myGroups) {
                        $userPost->whereIn('user_id', $friends);
                        $userPost->whereIn('group_id', $myGroups);
                    });
                })->orderBy('created_at', 'desc')->paginate(20);
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
                if (!$friends)
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                $likedItems = \OlaHub\UserPortal\Models\LikedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($friends) {
                        $q->where(function ($query) use ($friends) {
                            $query->whereIn('user_id', $friends);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($likedItems->count()) {
                    foreach ($likedItems as $litem) {
                        $uInfo = \OlaHub\UserPortal\Models\UserModel::find($litem->user_id);
                        $fInfo = [
                            'user_id' => $uInfo->id,
                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($uInfo->profile_picture),
                            'profile_url' => $uInfo->profile_url,
                            'username' => "$uInfo->first_name $uInfo->last_name",
                        ];
                        if ($litem->item_type == 'store') {
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $litem->item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_liked_store', $fInfo);
                        } else {
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $litem->item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_liked_designer', $fInfo);
                        }
                    }
                }
            } catch (Exception $ex) {
            }

            // shared items
            try {
                if (!$friends)
                    $friends = \OlaHub\UserPortal\Models\Friends::getFriendsList($user->id);
                $sharedItems = \OlaHub\UserPortal\Models\SharedItems::withoutGlobalScope('currentUser')
                    ->where(function ($q) use ($friends) {
                        $q->where(function ($query) use ($friends) {
                            $query->whereIn('user_id', $friends);
                        });
                    })->orderBy('created_at', 'desc')->paginate(20);
                if ($sharedItems->count()) {
                    foreach ($sharedItems as $sitem) {
                        $uInfo = \OlaHub\UserPortal\Models\UserModel::find($sitem->user_id);
                        $fInfo = [
                            'user_id' => $uInfo->id,
                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($uInfo->profile_picture),
                            'profile_url' => $uInfo->profile_url,
                            'username' => "$uInfo->first_name $uInfo->last_name",
                        ];
                        if ($sitem->item_type == 'store') {
                            $item = \OlaHub\UserPortal\Models\CatalogItem::where('id', $sitem->item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_shared_store', $fInfo);
                        } else {
                            $item = \OlaHub\UserPortal\Models\DesignerItems::where('id', $sitem->item_id)->first();
                            $timeline[] = $this->handlePostTimeline($item, 'item_shared_designer', $fInfo);
                        }
                    }
                }
            } catch (Exception $ex) {
            }
        }

        // merchants
        $merchants = \OlaHub\UserPortal\Models\Brand::whereRaw($month)->orderBy('created_at', 'desc')->paginate(20);
        foreach ($merchants as $merchant) {
            $timeline[] = $this->handlePostTimeline($merchant, 'merchant');
        }
        // designers
        $designers = \OlaHub\UserPortal\Models\Designer::whereRaw($month)->orderBy('created_at', 'desc')->paginate(20);
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
            if (!isset($itemsBrands[$item->store_id]))
                $itemsBrands[$item->store_id] = [];
            array_push($itemsBrands[$item->store_id], $item);
        }
        foreach ($itemsBrands as $m => $im) {
            if (count($im) == 1) {
                if (is_object($im))
                    $timeline[] = $this->handlePostTimeline($im, 'item');
            } else {
                $timeline[] = $this->handlePostTimeline($im, 'multi_item');
            }
        }

        // designer items
        $dItems = \OlaHub\UserPortal\Models\DesignerItems::where(function ($query) {
            $query->whereNull('parent_item_id');
            $query->orWhere('parent_item_id', '0');
        })->where('item_stock', '>', 0)->whereRaw($month)->inRandomOrder()->paginate(20);
        $itemsDesigners = [];
        foreach ($dItems as $item) {
            if (!isset($itemsDesigners[$item->designer_id]))
                $itemsDesigners[$item->designer_id] = [];
            array_push($itemsDesigners[$item->designer_id], $item);
        }
        foreach ($itemsDesigners as $d => $id) {
            if (count($id) == 1) {
                if (is_object($id))
                    $timeline[] = $this->handlePostTimeline($id, 'designer_item');
            } else {
                $timeline[] = $this->handlePostTimeline($id, 'designer_multi_item');
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
                        "desc" => isset($one->description) ? $one->description : NULL,
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
        if (!$page) {
            $return['celebrations'] = $celebrations;
            $return['upcoming'] = $upcoming;
        }
        return response($return, 200);
    }

    private function handlePostTimeline($data, $type, $fInfo = NULL)
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
            'time' => isset($data->created_at) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($data->created_at) : NULL,
            'user_info' => $fInfo ? $fInfo : $this->userInfo
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
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : NULL;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : NULL,
                    'merchant_title' => isset($brand->name) ? $brand->name : NULL,
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
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : NULL;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : NULL,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : NULL,
                ];
                break;
            case 'item':
                $brand = $data->brand;
                $images = $data->images;
                $return['target'] = 'store';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : NULL;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : NULL,
                    'merchant_title' => isset($brand->name) ? $brand->name : NULL,
                ];
                break;
            case 'designer_item':
                $designer = $data->designer;
                $images = $data->images;
                $return['target'] = 'designer';
                $return['item_slug'] = $data->item_slug;
                $return['item_title'] = $data->name;
                $return['item_desc'] = isset($data->description) ? strip_tags($data->description) : NULL;
                $return['avatar_url'] = count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : NULL,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : NULL,
                ];
                break;
            case 'merchant':
                $return['merchant_title'] = $data->name;
                $return['merchant_slug'] = isset($data->store_slug) ? $data->store_slug : NULL;
                $return['avatar_url'] = isset($data->image_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($data->image_ref) : NULL;
                break;
            case 'designer':
                $return['merchant_title'] = $data->brand_name;
                $return['merchant_slug'] = isset($data->designer_slug) ? $data->designer_slug : NULL;
                $return['avatar_url'] = isset($data->logo_ref) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($data->logo_ref) : NULL;
                break;
            case 'multi_item':
                $items = [];
                $brand = $data[0]->brand;
                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : NULL,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL,
                        'item_title' =>  $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : NULL,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'brand',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                    'merchant_slug' => isset($brand->store_slug) ? $brand->store_slug : NULL,
                    'merchant_title' => isset($brand->name) ? $brand->name : NULL,
                ];
                break;
            case 'designer_multi_item':
                $items = [];
                $designer = $data[0]->designer;
                foreach ($data as $item) {
                    $images = $item->images;
                    $items[] = [
                        'item_slug' => isset($item->item_slug) ? $item->item_slug : NULL,
                        'avatar_url' => count($images) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($images[0]->content_ref) : NULL,
                        'item_title' =>  $item->name,
                        'item_desc' => isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item->description, 10) : NULL,
                    ];
                }
                $return['items'] = $items;
                $return['merchant_info'] = [
                    'type' => 'designer',
                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                    'merchant_slug' => isset($designer->designer_slug) ? $designer->designer_slug : NULL,
                    'merchant_title' => isset($designer->brand_name) ? $designer->brand_name : NULL,
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
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => FALSE, 'msg' => 'shareBefore', 'code' => 204]]);
                (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
                return response(['status' => FALSE, 'msg' => 'shareBefore', 'code' => 204], 200);
            }

            $post->push('shares', app('session')->get('tempID'), true);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setLogSessionData(['response' => ['status' => TRUE, 'msg' => 'shareItem', 'code' => 200]]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            return response(['status' => TRUE, 'msg' => 'shareItem', 'code' => 200], 200);
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
        $following->type = ($type == 'brands' ? 1 : 2);
        $following->save();
        return response(['status' => true, 'msg' => 'follow successfully', 'code' => 200], 200);
    }

    public function userUnFollow($type, $id)
    {
        \OlaHub\UserPortal\Models\Following::where("user_id", app('session')->get('tempID'))->where('target_id', $id)
            ->where('type', ($type == 'brands' ? 1 : 2))->delete();
        return response(['status' => true, 'msg' => 'unfollow successfully', 'code' => 200], 200);
    }

    public function listUserFollowing()
    {
        $user = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
        $return = [];
        if ($user) {
            $brands = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 1)->get();
            $designers = \OlaHub\UserPortal\Models\Following::select('target_id')->where("user_id", app('session')->get('tempID'))->where('type', 2)->get();
            if (isset($brands)) {
                $brands = \OlaHub\UserPortal\Models\Brand::whereIn('id', $brands)->get();
                foreach ($brands as $brand) {
                    $return['brands']['data'][] = [
                        "brandID" => isset($brand->id) ? $brand->id : 0,
                        'brandName' => isset($brand->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, "name") : NULL,
                        'brandLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref),
                        'brandSlug' => isset($brand->store_slug) ? $brand->store_slug : null
                    ];
                }
            }
            if (isset($designers)) {
                $designers = \OlaHub\UserPortal\Models\Designer::whereIn('id', $designers)->get();
                foreach ($designers as $designer) {
                    $return['designer'][] = [
                        "designerId" => isset($designer->id) ? $designer->id : 0,
                        'designerName' => isset($designer->brand_name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, "brand_name") : NULL,
                        'designerLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                        'designerSlug' => isset($designer->designer_slug) ? $designer->designer_slug : null
                    ];
                }
            }
        }
        return response(['status' => true, 'data' => $return, 'code' => 200], 200);
    }
}
