<?php

namespace OlaHub\UserPortal\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class OlaHubGeneralController extends BaseController
{

    protected $requestData;
    protected $requestFilter;
    protected $userAgent;

    public function __construct(Request $request)
    {
        $return = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getRequest($request);
        $this->requestData = (object) $return['requestData'];
        $this->requestFilter = (object) $return['requestFilter'];
        $this->userAgent = $request->header('uniquenum') ? $request->header('uniquenum') : $request->header('user-agent');
        $this->requestShareData = $return['requestData'];
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
        $allCountries = \OlaHub\UserPortal\Models\ShippingCountries::selectRaw("countries.name as text, countries.id as value, phonecode, LOWER(code) as code")
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
        $interests = \OlaHub\UserPortal\Models\Interests::whereIn('countries', [app('session')->get('def_country')->id])->get();
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
        $notification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', (int) app('session')->get('tempID'))->orderBy("created_at", "DESC")->get();

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
            (new \OlaHub\UserPortal\Helpers\LogHelper)->saveLogSessionData();
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End check notification existance"]);
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "End fetch user notification"]);
            return $notification;
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
        $notification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', app('session')->get('tempID'))->orderBy("created_at", "DESC")->get();

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
                \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', app('session')->get('tempID'))->update(['read' => 1]);
                return ['status' => true, 'msg' => 'Notifications has been read', 'code' => 200];
            } else {
                $notification = \OlaHub\UserPortal\Models\NotificationMongo::where('for_user', app('session')->get('tempID'))->find($this->requestData->notificationId);
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
        return response(['status' => true, 'country' => strtoupper($country->two_letter_iso_code), 'code' => 200], 200);
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

            if (app('session')->get('tempID')) {
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search users"]);
                $users = \OlaHub\UserPortal\Models\UserModel::searchUsers($q, false, false, 0, TRUE);
                if ($users > 0) {
                    $searchData[] = [
                        "type" => "users"
                    ];
                }
                (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search interests"]);
                $groupInterests = \OlaHub\UserPortal\Models\Interests::searchInterests($q, 0);
                $groups = \OlaHub\UserPortal\Models\groups::searchGroups($q, 0, $groupInterests);
                if ($groups > 0) {
                    $searchData[] = [
                        "type" => "groups"
                    ];
                }
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search brands"]);
            $brands = \OlaHub\UserPortal\Models\Brand::searchBrands($q, 0);
            if ($brands > 0) {
                $searchData[] = [
                    "type" => "brands"
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search items"]);
            $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, 0);
            if ($items > 0) {
                $searchData[] = [
                    "type" => "items"
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search designer items"]);
            $desginerItems = \OlaHub\UserPortal\Models\DesginerItems::searchItems($q, 0);
            if ($desginerItems > 0) {
                $searchData[] = [
                    "type" => "desginer_items"
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search designers"]);
            $desginers = \OlaHub\UserPortal\Models\Designer::searchDesigners($q, 0);
            if ($desginers > 0) {
                $searchData[] = [
                    "type" => "designers"
                ];
            }
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search classifications"]);
            $classifications = \OlaHub\UserPortal\Models\Classification::searchClassifications($q, 0);
            if ($classifications->count() > 0) {
                $returnClasses = [];
                foreach ($classifications as $oneClass) {
                    $returnClasses[] = [
                        "label" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($oneClass, "name"),
                        "slug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($oneClass, "class_slug", $oneClass->name),
                    ];
                }
                if (count($returnClasses) > 0) {
                    $searchData[] = [
                        "type" => "classifications",
                        "typeData" => $returnClasses
                    ];
                }
            }
        }
        $return = ['status' => true, 'data' => $searchData, 'code' => 200];
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
                            //                            foreach ($users as $user) {
                            //                                $searchData[] = [
                            //                                    "itemId" => isset($user->id) ? $user->id : 0,
                            //                                    "itemName" => isset($user->first_name) ? $user->first_name . ' ' . $user->last_name : NULL,
                            //                                    "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($user->profile_picture),
                            //                                    "itemSlug" => \OlaHub\UserPortal\Models\UserModel::getUserSlug($user),
                            //                                    "itemType" => 'user'
                            //                                ];
                            //                            }
                        }
                    }
                    break;
                case "groups":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search groups filter"]);
                    if (app('session')->get('tempID')) {
                        $groupInterests = \OlaHub\UserPortal\Models\Interests::searchInterests($q, 0);
                        $groups = \OlaHub\UserPortal\Models\groups::searchGroups($q, $count, $groupInterests);
                        if ($groups->count() > 0) {
                            $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($groups, '\OlaHub\UserPortal\ResponseHandlers\GroupSearchResponseHandler');
                            //                            foreach ($groups as $group) {
                            //                                $searchData[] = [
                            //                                    "itemId" => isset($group->{"_id"}) ? $group->{"_id"} : 0,
                            //                                    "itemName" => isset($group->name) ? $group->name : NULL,
                            //                                    "itemImage" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($group->image),
                            //                                    "itemCover" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($group->cover),
                            //                                    "itemType" => 'group'
                            //                                ];
                            //                            }
                        }
                    }
                    break;
                case "brands":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search brands filter"]);
                    $brands = \OlaHub\UserPortal\Models\Brand::searchBrands($q, $count);
                    if ($brands->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandSearchResponseHandler');
                        //                        foreach ($brands as $brand) {
                        //                            $brandName = isset($brand->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($brand, 'name') : NULL;
                        //                            $brandImage = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($brand->image_ref);
                        //                            $merBrand = $brand->merchant()->first();
                        //                            $searchData[] = [
                        //                                "itemName" => $brandName,
                        //                                "itemImage" => $brandImage,
                        //                                "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($brand, 'store_slug', $brandName),
                        //                                "itemPhone" => isset($brand->contact_phone_no) ? $brand->contact_phone_no : NULL,
                        //                                "itememail" => isset($brand->contact_email) ? $brand->contact_email : NULL,
                        //                                "itemWebsite" => isset($merBrand->company_website) ? $merBrand->company_website : null,
                        //                                "itemAddress" => isset($merBrand->company_street_address) ? $merBrand->company_street_address : null,
                        //                                "itemType" => 'brand'
                        //                            ];
                        //                        }
                    }
                    break;
                case "items":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search items filter"]);
                    $items = \OlaHub\UserPortal\Models\CatalogItem::searchItem($q, $count);
                    if ($items->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($items, '\OlaHub\UserPortal\ResponseHandlers\ItemSearchResponseHandler');
                        //                        foreach ($items as $item) {
                        //                            $itemName = isset($item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') : NULL;
                        //                            $itemDescription = isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description') : NULL;
                        //                            $itemImage = $this->setDefImageData($item);
                        //                            $searchData[] = [
                        //                                "itemName" => $itemName,
                        //                                "itemDescription" => $itemDescription,
                        //                                "itemPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($item->price),
                        //                                "itemImage" => $itemImage,
                        //                                "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
                        //                                "itemType" => 'item'
                        //                            ];
                        //                        }
                    }
                    break;
                case "desginer_items":
                    (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Search desginer items filter"]);
                    $desginerItems = \OlaHub\UserPortal\Models\DesginerItems::searchItems($q, $count);
                    if ($desginerItems->count() > 0) {
                        $searchData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollectionPginate($desginerItems, '\OlaHub\UserPortal\ResponseHandlers\DesginerItemsSearchResponseHandler');
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
                            //                            foreach ($items as $item) {
                            //                                $itemName = isset($item->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'name') : NULL;
                            //                                $itemDescription = isset($item->description) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($item, 'description') : NULL;
                            //                                $itemImage = $this->setDefImageData($item);
                            //                                $searchData[] = [
                            //                                    "itemName" => $itemName,
                            //                                    "itemDescription" => $itemDescription,
                            //                                    "itemPrice" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setPrice($item->price),
                            //                                    "itemImage" => $itemImage,
                            //                                    "itemSlug" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($item, 'item_slug', $itemName),
                            //                                    "itemType" => 'classification',
                            //                                    "itemClassName" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($classification, 'name')
                            //                                ];
                            //                            }
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
            $userMongo = \OlaHub\UserPortal\Models\UserMongo::where("user_id", $user->id)->first();
            if (!$userMongo) {
                $userMongo = new \OlaHub\UserPortal\Models\UserMongo;
                $userMongo->user_id = (int) $user->id;
                $userMongo->my_groups = [];
                $userMongo->groups = [];
                $userMongo->celebrations = [];
                $userMongo->friends = [];
                $userMongo->requests = [];
                $userMongo->responses = [];
                $userMongo->intersts = [];
            }
            $userMongo->username = "$user->first_name $user->last_name";
            $userMongo->avatar_url = $user->profile_picture;
            $userMongo->country_id = $this->requestData->userCountry;
            $userMongo->gender = $user->user_gender;
            $userMongo->profile_url = $user->profile_url;
            $userMongo->cover_photo = $user->cover_photo;
            $userMongo->save();



            if (isset($this->requestData->isFriendsInvite) && $this->requestData->isFriendsInvite) {
                $loginedUser = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
                $loginedUser->push('requests', $user->id, true);
                $userMongo->push('responses', app('session')->get('tempID'), true);
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

    public function getUserTimeline()
    {
        $return = ['status' => false, 'msg' => 'NoData', 'code' => 204];
        $timeline = [];
        $friends = [];
        $now = date('Y-m-d');
        $all = [];
        $upcoming = [];
        $user = \OlaHub\UserPortal\Models\UserMongo::find(app('session')->get('tempID'));
        if ($user) {
            $friends = is_array($user->friends) ? $user->friends : [];
            if (count($friends) > 0) {
                $friendsCalendar = \OlaHub\UserPortal\Models\CalendarModel::whereIn('user_id', $friends)->where('calender_date', "<=", date("Y-m-d H:i:s", strtotime("+7 days")))->where('calender_date', ">", date("Y-m-d H:i:s"))->orderBy('calender_date', 'desc')->get();
                if (count($friendsCalendar) > 0) {
                    $upcomingData = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($friendsCalendar, '\OlaHub\UserPortal\ResponseHandlers\UpcomingEventsResponseHandler');
                    $upcoming = $upcomingData['data'];
                }
            }
            // $nonSeenGifts = \OlaHub\UserPortal\Models\UserBill::where('is_gift', 1)
            $nonSeenGifts = \DB::table('billing_history')->select("*")->where('is_gift', 1)
                ->where('gift_for', $user->user_id)
                ->where('gift_date', $now)
                ->where('seen', 0)
                ->get();
            foreach ($nonSeenGifts as $gift) {
                $gift_sender = \OlaHub\UserPortal\Models\UserModel::find($gift->user_id);
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
                // \OlaHub\UserPortal\Models\UserBill::where('is_gift', 1)
                \DB::table('billing_history')->where('is_gift', 1)
                    ->where('gift_for', app('session')->get('tempID'))
                    ->where('gift_date', $now)
                    ->update(["seen" => 1]);
           }
            //posts
            try {
                $currentCountryID = (int) app('session')->get('def_country')->id;
                $friends[] = (int) app('session')->get('tempID');
                $posts = \OlaHub\UserPortal\Models\Post::where(function ($q) use ($currentCountryID, $friends) {
                    $q->where(function ($userPost) use ($friends) {
                        $userPost->whereIn('user_id', $friends);
                        $userPost->where('friend_id', NULL);
                    });
                    $q->orWhere(function ($userPost) use ($friends) {
                        $userPost->where('friend_id', (int) app('session')->get('tempID'));
                    });
                    $q->orWhere(function ($storePosts) use ($currentCountryID) {
                        $storePosts->whereNull('user_id');
                        $storePosts->where('country_id', $currentCountryID);
                        // $storePosts->orWhere('country', $currentCountryID);
                    });
                })->orderBy('created_at', 'desc')->whereNull('group_id')->paginate(20);

                if ($posts->count() > 0) {
                    foreach ($posts as $post) {
                        if ($post->type) {
                            switch ($post->type) {
                                case 'store_post':
                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }
                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            $likerData[] = [
                                                'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                            ];
                                        }
                                    }
                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $timeline[] = [
                                        'type' => 'merchant',
                                        'post' => isset($post->_id) ? $post->_id : 0,
                                        'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                        'comments' => [],
                                        'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                        'liked' => $liked,
                                        'likersData' => $likerData,
                                        'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                        'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                        'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                        'user_info' => [
                                            'user_id' => $author->id,
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                            'username' => $authorName,
                                        ]
                                    ];
                                    break;
                                case 'designer_post':
                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }
                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            $likerData[] = [
                                                'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                            ];
                                        }
                                    }
                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $timeline[] = [
                                        'type' => 'designer',
                                        'post' => isset($post->_id) ? $post->_id : 0,
                                        'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                        'comments' => [],
                                        'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                        'liked' => $liked,
                                        'likersData' => $likerData,
                                        'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                        'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                        'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                        'user_info' => [
                                            'user_id' => $author->id,
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                            'username' => $authorName,
                                        ]
                                    ];
                                    break;
                                case 'item_post':
                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }
                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    $friendslikerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            if (in_array($like, $friends)) {
                                                $friendslikerData[] = [
                                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'likerName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $likerData[] = [
                                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'likerName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $wishlists = isset($post->wishlists) && is_array($post->wishlists) ? $post->wishlists : [];
                                    $wishlistsData = [];
                                    $friendsWishlistsData = [];
                                    foreach ($wishlists as $wishlist) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $wishlist)->first();
                                        if ($userData) {
                                            if (in_array($wishlist, $friends)) {
                                                $friendsWishlistsData[] = [
                                                    'wishlistUserPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'wishlistUserProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'wishlistUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $wishlistsData[] = [
                                                    'wishlistUserPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'wishlistUserProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'wishlistUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $shares = isset($post->shares) && is_array($post->shares) ? $post->shares : [];
                                    $shareData = [];
                                    $friendsShareData = [];
                                    foreach ($shares as $share) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $share)->first();
                                        if ($userData) {
                                            if (in_array($share, $friends)) {
                                                $friendsShareData[] = [
                                                    'sharePhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'shareProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'shareUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $shareData[] = [
                                                    'sharePhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'shareProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'shareUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $timeline[] = [
                                        'type' => 'item',
                                        'post' => isset($post->_id) ? $post->_id : 0,
                                        'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                        'comments' => [],
                                        'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                        'liked' => $liked,
                                        'likersData' => $likerData,
                                        'friendsLikerData' => $friendslikerData,
                                        'shareData' => $shareData,
                                        'friendsShareData' => $friendsShareData,
                                        'wishlistsData' => $wishlistsData,
                                        'friendsWishlistsData' => $friendsWishlistsData,
                                        'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                        'item_slug' => isset($post->item_slug) ? $post->item_slug : NULL,
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl((isset($post->post_image) ? $post->post_image : false)),
                                        'item_title' => isset($post->item_name) ? $post->item_name : NULL,
                                        'item_desc' => isset($post->item_description) ? strip_tags($post->item_description) : NULL,
                                        'merchant_info' => [
                                            'type' => 'brand',
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                            'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                            'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                        ],
                                        'user_info' => [
                                            'user_id' => $author->id,
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                            'username' => $authorName,
                                        ]
                                    ];
                                    break;
                                case 'designer_item_post':
                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }
                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    $friendslikerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            if (in_array($like, $friends)) {
                                                $friendslikerData[] = [
                                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'likerName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $likerData[] = [
                                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'likerName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $wishlists = isset($post->wishlists) && is_array($post->wishlists) ? $post->wishlists : [];
                                    $wishlistsData = [];
                                    $friendsWishlistsData = [];
                                    foreach ($wishlists as $wishlist) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $wishlist)->first();
                                        if ($userData) {
                                            if (in_array($wishlist, $friends)) {
                                                $friendsWishlistsData[] = [
                                                    'wishlistUserPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'wishlistUserProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'wishlistUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $wishlistsData[] = [
                                                    'wishlistUserPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'wishlistUserProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'wishlistUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $shares = isset($post->shares) && is_array($post->shares) ? $post->shares : [];
                                    $shareData = [];
                                    $friendsShareData = [];
                                    foreach ($shares as $share) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $share)->first();
                                        if ($userData) {
                                            if (in_array($share, $friends)) {
                                                $friendsShareData[] = [
                                                    'sharePhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'shareProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'shareUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            } else {
                                                $shareData[] = [
                                                    'sharePhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'shareProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL,
                                                    'shareUserName' => isset($userData->username) ? $userData->username : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $timeline[] = [
                                        'type' => 'designer_item',
                                        'post' => isset($post->_id) ? $post->_id : 0,
                                        'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                        'comments' => [],
                                        'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                        'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                        'liked' => $liked,
                                        'likersData' => $likerData,
                                        'friendsLikerData' => $friendslikerData,
                                        'shareData' => $shareData,
                                        'friendsShareData' => $friendsShareData,
                                        'wishlistsData' => $wishlistsData,
                                        'friendsWishlistsData' => $friendsWishlistsData,
                                        'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                        'item_slug' => isset($post->item_slug) ? $post->item_slug : NULL,
                                        'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl((isset($post->post_image) ? $post->post_image : false)),
                                        'item_title' => isset($post->item_name) ? $post->item_name : NULL,
                                        'item_desc' => isset($post->item_description) ? strip_tags($post->item_description) : NULL,
                                        'merchant_info' => [
                                            'type' => 'designer',
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                            'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                            'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                        ],
                                        'user_info' => [
                                            'user_id' => $author->id,
                                            'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                            'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                            'username' => $authorName,
                                        ]
                                    ];
                                    break;
                                case 'multi_item_post':
                                    $items = [];
                                    if ($post->items && is_array($post->items)) {
                                        foreach ($post->items as $item) {
                                            if (isset($item['item_image']) && $item['item_image']) {
                                                $items[] = [
                                                    'item_slug' => isset($item['item_slug']) ? $item['item_slug'] : NULL,
                                                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl((isset($item['item_image']) ? $item['item_image'] : false)),
                                                    'item_title' => isset($item['item_name']) ? $item['item_name'] : NULL,
                                                    'item_desc' => isset($item['item_description']) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item['item_description'], 10) : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }

                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            $likerData[] = [
                                                'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                            ];
                                        }
                                    }


                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $merchantIndex = $this->getMerchantIndexFromSlug($timeline, $post->store_slug);
                                    if ($merchantIndex === false) {
                                        $timeline[] = [
                                            'type' => 'multi_item',
                                            'post' => isset($post->_id) ? $post->_id : 0,
                                            'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                            'comments' => [],
                                            'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                            'liked' => $liked,
                                            'likersData' => $likerData,
                                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                            'items' => $items,
                                            'merchant_info' => [
                                                'type' => 'brand',
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                                'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                                'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                            ],
                                            'user_info' => [
                                                'user_id' => $author->id,
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                                'username' => $authorName,
                                            ]
                                        ];
                                    } else {
                                        $timeLineMerchant = $timeline[$merchantIndex];
                                        $oldItems = $timeLineMerchant["items"];
                                        if (is_array($oldItems) && count($oldItems) > 0) {
                                            foreach ($items as $oneItem) {
                                                if (count($oldItems) >= 26) break;
                                                $oldItems[] = $oneItem;
                                            }
                                            $timeline[$merchantIndex]["items"] = $oldItems;
                                        }
                                    }

                                    break;
                                case 'designer_multi_item_post':
                                    $items = [];
                                    if ($post->items && is_array($post->items)) {
                                        foreach ($post->items as $item) {
                                            if (isset($item['item_image']) && $item['item_image']) {
                                                $items[] = [
                                                    'item_slug' => isset($item['item_slug']) ? $item['item_slug'] : NULL,
                                                    'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl((isset($item['item_image']) ? $item['item_image'] : false)),
                                                    'item_title' => isset($item['item_name']) ? $item['item_name'] : NULL,
                                                    'item_desc' => isset($item['item_description']) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getWordsFromString($item['item_description'], 10) : NULL,
                                                ];
                                            }
                                        }
                                    }

                                    $liked = 0;
                                    if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                        $liked = 1;
                                    }

                                    $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                    $likerData = [];
                                    foreach ($likes as $like) {
                                        $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                        if ($userData) {
                                            $likerData[] = [
                                                'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                            ];
                                        }
                                    }


                                    $author = app('session')->get('tempData');
                                    $authorName = "$author->first_name $author->last_name";
                                    $merchantIndex = $this->getMerchantIndexFromSlug($timeline, $post->store_slug);
                                    if ($merchantIndex === false) {
                                        $timeline[] = [
                                            'type' => 'designer_multi_item',
                                            'post' => isset($post->_id) ? $post->_id : 0,
                                            'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                            'comments' => [],
                                            'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                            'liked' => $liked,
                                            'likersData' => $likerData,
                                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                            'items' => $items,
                                            'merchant_info' => [
                                                'type' => 'designer',
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->store_logo),
                                                'merchant_slug' => isset($post->store_slug) ? $post->store_slug : NULL,
                                                'merchant_title' => isset($post->store_name) ? $post->store_name : NULL,
                                            ],
                                            'user_info' => [
                                                'user_id' => $author->id,
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                                'username' => $authorName,
                                            ]
                                        ];
                                    } else {
                                        $timeLineMerchant = $timeline[$merchantIndex];
                                        $oldItems = $timeLineMerchant["items"];
                                        if (is_array($oldItems) && count($oldItems) > 0) {
                                            foreach ($items as $oneItem) {
                                                if (count($oldItems) >= 26) break;
                                                $oldItems[] = $oneItem;
                                            }
                                            $timeline[$merchantIndex]["items"] = $oldItems;
                                        }
                                    }

                                    break;
                                case 'post':
                                    $author = \OlaHub\UserPortal\Models\UserModel::find($post->user_id);
                                    $friend = \OlaHub\UserPortal\Models\UserModel::find($post->friend_id);
                                    if ($author) {
                                        $authorName = "$author->first_name $author->last_name";
                                        $liked = 0;
                                        if (is_array($post->likes) && in_array(app('session')->get('tempID'), $post->likes)) {
                                            $liked = 1;
                                        }

                                        $likes = isset($post->likes) && is_array($post->likes) ? $post->likes : [];
                                        $likerData = [];
                                        foreach ($likes as $like) {
                                            $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                                            if ($userData) {
                                                $likerData[] = [
                                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                                ];
                                            }
                                        }

                                        $images = [];
                                        if (is_array($post->post_image) && count($post->post_image) > 0) {
                                            foreach ($post->post_image as $oneImage) {
                                                $images[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($oneImage);
                                            }
                                        } elseif ($post->post_image) {
                                            $images[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->post_image);
                                        }

                                        $videos = [];
                                        if (is_array($post->post_video) && count($post->post_video) > 0) {
                                            foreach ($post->post_video as $oneVideo) {
                                                $videos[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($oneVideo);
                                            }
                                        } elseif ($post->post_video) {
                                            $videos[] = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($post->post_video);
                                        }

                                        $timeline[] = [
                                            'type' => 'post',
                                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($post->created_at),
                                            'post' => isset($post->_id) ? $post->_id : 0,
                                            'content' => isset($post->post) ? $post->post : NULL,
                                            'color' => isset($post->color) ? $post->color : NULL,
                                            'subject' => isset($post->subject) ? $post->subject : NULL,
                                            'total_share_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'comments_count' => isset($post->comments) ? count($post->comments) : 0,
                                            'comments' => [],
                                            'shares_count' => isset($post->shares) ? count($post->shares) : 0,
                                            'likers_count' => isset($post->likes) ? count($post->likes) : 0,
                                            'liked' => $liked,
                                            'likersData' => $likerData,
                                            'post_img' => $images,
                                            'post_video' => $videos,
                                            'friendId' => isset($post->friend_id) ? $post->friend_id : NULL,
                                            'user_info' => [
                                                'user_id' => $author->id,
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($author->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($author, 'profile_url', $authorName, '.'),
                                                'username' => $authorName,
                                            ],
                                            'friend_info' => ($friend ? [
                                                'avatar_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($friend->profile_picture),
                                                'profile_url' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkSlug($friend, 'profile_url', "$friend->first_name $friend->last_name", '.'),
                                                'username' => "$friend->first_name $friend->last_name",
                                                'user_id' => $friend->id
                                            ] : NULL)
                                        ];
                                        break;
                                    }
                            }
                        }
                    }
                }
            } catch (Exception $ex) {
            }
        }




        //celebration
        $celebrations = [];
        try {
            $participants = \OlaHub\UserPortal\Models\CelebrationParticipantsModel::where('user_id', app('session')->get('tempID'))->get();

            if ($participants->count() > 0) {
                foreach ($participants as $participant) {
                    $celebrationContents = \OlaHub\UserPortal\Models\CelebrationContentsModel::where('celebration_id', $participant->celebration_id)->orderBy('created_at', 'DESC')->paginate(5);
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




        try {
            $groupPosts = \OlaHub\UserPortal\Models\Post::where('user_id', (int) app('session')->get('tempID'))->where('isApprove', 1)->whereNotNull('group_id')->orderBy('created_at', 'desc')->paginate(5);
            $type = '';
            if ($groupPosts->count() > 0) {
                foreach ($groupPosts as $groupPost) {
                    $author = \OlaHub\UserPortal\Models\UserModel::where('id', $groupPost->user_id)->first();
                    if ($author) {
                        $authorName = "$author->first_name $author->last_name";
                        $liked = 0;
                        if (is_array($groupPost->likes) && in_array(app('session')->get('tempID'), $groupPost->likes)) {
                            $liked = 1;
                        }
                        $likes = isset($groupPost->likes) && is_array($groupPost->likes) ? $groupPost->likes : [];
                        $likerData = [];
                        foreach ($likes as $like) {
                            $userData = \OlaHub\UserPortal\Models\UserMongo::where('user_id', $like)->first();
                            if ($userData) {
                                $likerData[] = [
                                    'likerPhoto' => isset($userData->avatar_url) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($userData->avatar_url) : \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl(false),
                                    'likerProfileSlug' => isset($userData->profile_url) ? $userData->profile_url : NULL
                                ];
                            }
                        }
                        $timeline[] = [
                            "type" => 'group',
                            'post' => isset($groupPost->_id) ? $groupPost->_id : 0,
                            'color' => isset($post->color) ? $post->color : NULL,
                            'total_share_count' => isset($groupPost->shares) ? count($groupPost->shares) : 0,
                            'comments_count' => isset($groupPost->comments) ? count($groupPost->comments) : 0,
                            'comments' => [],
                            'shares_count' => isset($groupPost->shares) ? count($groupPost->shares) : 0,
                            'likers_count' => isset($groupPost->likes) ? count($groupPost->likes) : 0,
                            'liked' => $liked,
                            'likersData' => $likerData,
                            'post_img' => $groupPost->post_image ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($groupPost->post_image) : null,
                            'post_video' => $groupPost->post_video ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($groupPost->post_video) : null,
                            'subject' => isset($groupPost->subject) ? $groupPost->subject : NULL,
                            'content' => isset($groupPost->post) ? $groupPost->post : NULL,
                            "group_id" => isset($groupPost->group_id) ? $groupPost->group_id : NULL,
                            "groupName" => isset($groupPost->group_title) ? $groupPost->group_title : NULL,
                            'time' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::timeElapsedString($groupPost->created_at),
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
        } catch (Exception $ex) {
        }

        // Sponsers
        $sponsers_arr = [];
        try {
            $timelinePosts = \DB::table('campaign_slot_prices')->where('country_id', app('session')->get('def_country')->id)->where('is_post', '1')->get();
            if ($timelinePosts->count() > 0) {
                foreach ($timelinePosts as $onePost) {
                    $sponsers = \OlaHub\Models\AdsMongo::where('slot', $onePost->id)->where('country', app('session')->get('def_country')->id)->orderBy('id', 'RAND()')->paginate(5);
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
            }
        } catch (Exception $ex) {
        }
        // try {
        //     $seenGifts = \OlaHub\UserPortal\Models\UserBill::where('is_gift', 1)
        //         ->where('gift_for', app('session')->get('tempID'))
        //         ->where('gift_date', $now)
        //         ->where('seen', 1)
        //         ->paginate(5);
        //     foreach ($seenGifts as $gift) {
        //         $gift_sender = \OlaHub\UserPortal\Models\UserModel::find($gift->user_id);
        //         $items = \OlaHub\UserPortal\Models\UserBillDetails::where('billing_id', $gift->id)->get();
        //         $seenGiftsResponse = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($items, '\OlaHub\UserPortal\ResponseHandlers\PurchasedItemResponseHandler');
        //         $timeline[] = [
        //             'type' => 'gift',
        //             'gift_sender' => $gift_sender,
        //             'message' => isset($gift->gift_message) ? $gift->gift_message : "",
        //             'video' => isset($gift->gift_video_ref) ? $gift->gift_video_ref : "",
        //             'items' => $seenGiftsResponse['data']
        //         ];
        //     }
        // } catch (Exception $ex) {
        // }
        if (count($timeline) > 0) {
            // shuffle($timeline);

            $count_timeline = count($timeline);
            $count_sponsers = count($sponsers_arr);
            $break = ($count_sponsers - 1) > 0 ? (int) ($count_timeline / $count_sponsers - 1) : 0;
            $start_in = 0;
            for ($i = 0; $i < count($timeline); $i++) {
                $all[] = $timeline[$i];
                if ($break - 1 == $i) {
                    $all[] = $sponsers_arr[$start_in];
                    $start_in++;
                    $break = $break * 2;
                }
            }

            $return = ['status' => true, 'data' => $all, 'celebrations' => $celebrations, 'upcoming' => $upcoming, 'code' => 200];
        }
        //        $logHelper = new \OlaHub\UserPortal\Helpers\LogHelper;
        //        $logHelper->setLog("", $return, 'getUserTimeline', $this->userAgent);
        return response($return, 200);
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
        $user = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
        if (!$user) {
            $user = new \OlaHub\UserPortal\Models\UserMongo;
            $user->user_id = (int) app('session')->get('tempID');
            $user->my_groups = [];
            $user->groups = [];
            $user->celebrations = [];
            $user->friends = [];
            $user->requests = [];
            $user->responses = [];
            $user->intersts = [];
            $user->followed_brands = [];
            $user->followed_occassions = [];
            $user->followed_designers = [];
            $user->followed_interests = [];
            $user->username = app('session')->get('tempData')->first_name . " " . app('session')->get('tempData')->last_name;
            $user->avatar_url = app('session')->get('tempData')->profile_picture;
            $user->country_id = app('session')->get('def_country')->id;
            $user->gender = app('session')->get('tempData')->user_gender;
            $user->profile_url = app('session')->get('tempData')->profile_url;
            $user->cover_photo = app('session')->get('tempData')->cover_photo;
            $user->save();
        }

        $key = "followed_" . $type;
        \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->push($key, $id, true);
        return response(['status' => true, 'msg' => 'follow successfully', 'code' => 200], 200);
    }

    public function userUnFollow($type, $id)
    {
        $user = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
        if (!$user) {
            $user = new \OlaHub\UserPortal\Models\UserMongo;
            $user->user_id = (int) app('session')->get('tempID');
            $user->my_groups = [];
            $user->groups = [];
            $user->celebrations = [];
            $user->friends = [];
            $user->requests = [];
            $user->responses = [];
            $user->intersts = [];
            $user->followed_brands = [];
            $user->followed_occassions = [];
            $user->followed_designers = [];
            $user->followed_interests = [];
            $user->username = app('session')->get('tempData')->first_name . " " . app('session')->get('tempData')->last_name;
            $user->avatar_url = app('session')->get('tempData')->profile_picture;
            $user->country_id = app('session')->get('def_country')->id;
            $user->gender = app('session')->get('tempData')->user_gender;
            $user->profile_url = app('session')->get('tempData')->profile_url;
            $user->cover_photo = app('session')->get('tempData')->cover_photo;
            $user->save();
        }

        $key = "followed_" . $type;
        if (isset($user->{$key}) && is_array($user->{$key})) {
            \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->pull($key, $id);
            return response(['status' => true, 'msg' => 'unfollow successfully', 'code' => 200], 200);
        }
        return response(['status' => false, 'msg' => 'unknown type and id', 'code' => 404], 200);
    }

    public function listUserFollowing()
    {
        $user = \OlaHub\UserPortal\Models\UserMongo::where('user_id', app('session')->get('tempID'))->first();
        if ($user) {
            $return = [];
            if (isset($user->followed_brands) && is_array($user->followed_brands) && count($user->followed_brands) > 0) {
                $brands = \OlaHub\UserPortal\Models\Brand::whereIn('id', $user->followed_brands)->get();
                $return['brands'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($brands, '\OlaHub\UserPortal\ResponseHandlers\BrandsResponseHandler');
            }
            if (isset($user->followed_occassions) && is_array($user->followed_occassions) && count($user->followed_occassions) > 0) {
                $occassions = \OlaHub\UserPortal\Models\Occasion::whereIn('id', $user->followed_occassions)->get();
                $return['occassions'] = \OlaHub\UserPortal\Helpers\CommonHelper::handlingResponseCollection($occassions, '\OlaHub\UserPortal\ResponseHandlers\OccasionsResponseHandler');
            }
            if (isset($user->followed_designers) && is_array($user->followed_designers) && count($user->followed_designers) > 0) {
                $designers = \OlaHub\UserPortal\Models\Designer::whereIn('id', $user->followed_designers)->get();
                foreach ($designers as $designer) {
                    $return['designer'][] = [
                        "designerId" => isset($designer->id) ? $designer->id : 0,
                        'designerName' => isset($designer->brand_name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($designer, "brand_name") : NULL,
                        'designerLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($designer->logo_ref),
                        'designerSlug' => isset($designer->designer_slug) ? $designer->designer_slug : null
                    ];
                }
            }
            if (isset($user->followed_interests) && is_array($user->followed_interests) && count($user->followed_interests) > 0) {
                $interests = \OlaHub\UserPortal\Models\Interests::whereIn('id', $user->followed_interests)->get();
                foreach ($interests as $interest) {
                    $return['interests'][] = [
                        'interestName' => isset($interest->name) ? \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::returnCurrentLangField($interest, "name") : NULL,
                        'interestLogo' => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($interest->image),
                        'interestSlug' => isset($interest->interest_slug) ? $interest->interest_slug : null
                    ];
                }
            }
            return response(['status' => true, 'data' => $return, 'code' => 200], 200);
        }
        return response(['status' => false, 'msg' => 'NoData', 'code' => 204], 200);
    }
}
