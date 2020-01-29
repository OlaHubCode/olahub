<?php

namespace OlaHub\UserPortal\Helpers;

use Stichoza\GoogleTranslate\TranslateClient;

abstract class OlaHubCommonHelper {

    static function timeStampToDate($time, $format = 'D d F, Y') {
        $return = $time;
        if ($time && $time > 0) {
            $return = date($format, $time);
        } else {
            $return = NULL;
        }
        return $return;
    }

    static function convertStringToDate($string, $format = 'D d F, Y') {
        $return = $string;
        if ($string) {
            $time = strtotime($string);
            if ($time && $time > 0) {
                $return = date($format, $time);
            } else {
                $return = NULL;
            }
        }
        return $return;
    }

    static function convertStringToDateTime($string, $format = 'D d F, Y H:i A') {
        $return = $string;
        if ($string) {
            $time = strtotime($string);
            if ($time && $time > 0) {
                $return = date($format, $time);
            } else {
                $return = NULL;
            }
        }
        return $return;
    }

    static function createSlugFromString($string, $delimiter = '-') {
        $return = $string;
        if ($string) {
            $return = str_replace(' ', '_', $string);
            $return = preg_replace("/[^-_a-zA-Z0-9]/", '', $return);
            $return = strtolower(trim($return, '-'));
            $return = preg_replace("/[\/_|+ -]+/", $delimiter, $return);
        }

        return $return;
    }

    static function checkSlug($model, $column, $originalName, $delimiter = '-') {
        $return = NULL;
        if ($model) {
            if ($model->$column) {
                $return = $model->$column;
            } else {
                $slug = OlaHubCommonHelper::createSlugFromString($originalName, $delimiter);
                $model->$column = $slug;
                $model->save();
                $return = $slug;
            }
        }
        return $return;
    }

    static function returnCurrentLangField($objectData, $fieldName) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Return field depending on current language", "action_startData" => json_encode($objectData) . $fieldName]);
        $return = NULL;
        $languageArray = explode("_", app('session')->get('def_lang')->default_locale);
        $language = strtolower($languageArray[0]);
        if (isset($objectData->$fieldName)) {
            $jsonData = json_decode($objectData->$fieldName);
            if (is_object($jsonData)) {
                if (isset($jsonData->$language) && !empty($jsonData->$language)) {
                    $return = $jsonData->$language;
                } else {
                    foreach ($jsonData as $key => $value) {
                        $return = $jsonData->$key;
                        break;
                    }
                }
            } else {
                $return = $objectData->$fieldName;
            }
        }
        return $return;
    }

    static function returnCurrentLangName($data) {
        $return = NULL;
        $languageArray = explode("_", app('session')->get('def_lang')->default_locale);
        $language = strtolower($languageArray[0]);
        if (isset($data)) {
            $jsonData = json_decode($data);
            if (is_object($jsonData)) {
                if (isset($jsonData->$language) && !empty($jsonData->$language)) {
                    $return = $jsonData->$language;
                } else {
                    foreach ($jsonData as $key => $value) {
                        $return = $jsonData->$key;
                        break;
                    }
                }
            } else {
                $return = $data;
            }
        }
        return $return;
    }

    static function defineRowCreator($data, $creatorColumn = 'created_by') {
        return isset($data->$creatorColumn) && $data->$creatorColumn > 0 ? $data->$creatorColumn : NULL;
    }

    static function defineRowUpdater($data, $updaterColumn = 'updated_by') {
        return isset($data->$updaterColumn) && $data->$updaterColumn > 0 ? $data->$updaterColumn : NULL;
    }

    public static function randomString($length = 8, $type = false) {
        switch ($type) {
            case 'str':
                $seed = str_split('abdefghijkmnqrtyABDEFGHJKLMNQRTY');
                break;
            case 'str_num':
                $seed = str_split('abdefghijkmnqrtyABDEFGHJKLMNQRTY123456789');
                break;
            case 'num':
                $seed = str_split('1234567890');
                break;
            case 'spc':
                $seed = str_split('!@$%^&*');
                break;
            case 'num_spc':
                $seed = str_split('1234567890!@$%^&*');
                break;
            case 'str_spc':
                $seed = str_split('abdefghijkmnqrtyABDEFGHJKLMNQRTY!@$%^&*');
                break;
            default :
                $seed = str_split('abdefghijkmnqrtyABDEFGHJKLMNQRTY123456789!@$%^&*');
                break;
        }

        shuffle($seed);
        $rand = '';
        foreach (array_rand($seed, 6) as $k) {
            $rand .= $seed[$k];
            shuffle($seed);
        }
        // foreach (array_rand($seed, $length) as $k) {
        //     $rand .= $seed[$k];
        // }
        return $rand;
    }

    static function translate($string) {
        $languages = \OlaHub\UserPortal\Models\Language::all();
        $return = [];
        $tr = new TranslateClient(null, 'ar');
        $tr->translate($string);
        $return[$tr->getLastDetectedSource()] = $string;
        foreach ($languages as $one) {
            $language = explode('_', $one->default_locale);
            $languageCode = isset($language[0]) ? $language[0] : $language;
            if (!array_key_exists($one->default_locale, $return)) {
                $tr = new TranslateClient();
                $return[$one->default_locale] = $tr->setTarget($languageCode)->translate($string);
            }
        }
        return json_encode($return);
    }

    static function setContentUrl($imageID, $type = 'image') {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Set Url for the content", "action_startData" => json_encode($imageID) . $type]);
        $return = null;
        if (STORAGE_URL) {
            $defult_url = STORAGE_URL;
        } else {
            $defult_url = url();
        }
        if (is_string($imageID) && strlen($imageID) > 4) {
            $imageID = str_replace("files", "files/", $imageID);
            $imageID = str_replace("temp_photos/", "", $imageID);
            if (strpos($imageID, "http") !== false || strpos($imageID, "https") !== false) {
                return $imageID;
            }
            $explodedData = explode('.', $imageID);
            $extension = end($explodedData);
            if (in_array(strtolower($extension), VIDEO_EXT)) {
                $return = $defult_url . "/$imageID";
            } elseif (in_array(strtolower($extension), IMAGE_EXT)) {
                if ((strtolower($type) == "cover_photo" || strtolower($type) == 'shop_banner')) {
                    $ctx = stream_context_create(
                            array(
                                "ssl" => array(
                                    "verify_peer" => false,
                                    "verify_peer_name" => false,
                                ),
                            )
                    );

                    $image = @file_get_contents($defult_url . "/$imageID", false, $ctx);
                    $size = @getimagesizefromstring($image);
                    if ($size) {
                        $return = $defult_url . "/$imageID";
                    } elseif (strtolower($type) == "cover_photo") {
                        $return = NULL;
                    } elseif ($type == 'shop_banner') {
                        $banner = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "shop")->first();
                        if ($banner && \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkContentUrl($banner->content_ref)) {
                            $return = [
                                "sliderRef" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($banner->content_ref),
                                "sliderText" => isset($banner->content_text) ? $banner->content_text : NULL,
                                "sliderLink" => isset($banner->content_link) ? $banner->content_link : NULL,
                            ];
                        }
                    }
                } else {
                    $return = $defult_url . "/$imageID";
                }
            }
        }
        if (!$return) {
            if (strtolower($type) == "cover_photo") {
                $return = NULL;
            } elseif ($type == 'shop_banner') {
                $banner = \OlaHub\UserPortal\Models\CompanyStaticData::ofType("slider", "shop")->first();
                if ($banner && \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::checkContentUrl($banner->content_ref)) {
                    $return = [
                        "sliderRef" => \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::setContentUrl($banner->content_ref),
                        "sliderText" => isset($banner->content_text) ? $banner->content_text : NULL,
                        "sliderLink" => isset($banner->content_link) ? $banner->content_link : NULL,
                    ];
                }
            } elseif ($type == 'community') {
                $return = "https://olahub.com/img/no_community.png";
            } else {
                $return = $defult_url . constant('DEF_' . strtoupper($type));
            }
        }
        return $return;
    }

    static function checkContentUrl($imageID) {
        $return = false;
        if (STORAGE_URL) {
            $defult_url = STORAGE_URL;
        } else {
            $defult_url = url();
        }
        if (strlen($imageID) > 4) {
            $imageID = str_replace("files", "files/", $imageID);
            $imageID = str_replace("temp_photos/", "", $imageID);
            if (strpos($imageID, "http") !== false || strpos($imageID, "https") !== false) {
                return $imageID;
            }
            $explodedData = explode('.', $imageID);
            $extension = end($explodedData);
            if (in_array(strtolower($extension), VIDEO_EXT)) {

                $return = TRUE;
            } elseif (in_array($extension, IMAGE_EXT)) {
                $return = TRUE;
            }
        }

        return $return;
    }

    static function setDefLang($country) {
        $countryCode = $country;
        if ($countryCode && $countryCode > 0) {
            $country = \OlaHub\UserPortal\Models\Country::find($countryCode);
            if ($country) {
                $defCountry = $country->id;
            }
        }

        $language = \OlaHub\UserPortal\Models\Language::find($country->language_id);
        if ($language) {
            $defLang = $language; //explode('_', $language->default_locale)[0];
        }
        app('session')->put('def_lang', $defLang);
    }

    static function getDefineConst($constName, $constVal = 'false') {
        if (defined($constName)) {
            if ($constVal !== 'false') {
                runkit_constant_redefine($constName, $constVal);
            }
        } else {
            define($constName, $constVal);
        }
        return constant('self::' . $constName);
    }

    static function setPrice($itemPrice, $withCurr = true, $countryID = false) {
        $price = (double)$itemPrice;
        if ($countryID) {
            $country = \OlaHub\UserPortal\Models\Country::find($countryID);
            $currency = $country->currencyData;
        } else {
            $currency = app('session')->get('def_currency');
        }
        $returnPrice = number_format($price, 2,'.','');
        // $returnPrice = number_format($price, 2,'.',',');
        if ($withCurr) {
            $returnCur = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency->code);
            return "$returnPrice $returnCur";
        }
        return $returnPrice;
    }

    static function setDesignerPrice($itemPrice, $withCurr = true) {
        $price = (double)$itemPrice;
        $currency = app('session')->get('def_currency');
        $exchangeRate = \DB::table("currencies_exchange_rates")->where("currency_to", $currency->code)->first();
        if($exchangeRate){
            $newPrice = $price * $exchangeRate->exchange_rate;
            // $returnPrice = number_format($newPrice, 2);
            $returnPrice = number_format($newPrice, 2,'.','');
        }else{
            // $returnPrice = number_format($price, 2);
            $returnPrice = number_format($price, 2,'.','');
        }
        
        if ($withCurr) {
            $returnCur = \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getTranslatedCurrency($currency->code);
            return "$returnPrice $returnCur";
        }
        return $returnPrice;
    }

    static function handlingResponseCollection($data, $responseHandler) {
        $collection = $data;
        $fractal = new \League\Fractal\Manager();
        $resource = new \League\Fractal\Resource\Collection($collection, new $responseHandler);
        return $fractal->createData($resource)->toArray();
    }

    static function handlingResponseItem($data, $responseHandler) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Handel response for item", "action_startData" => $data . $responseHandler]);
        $fractal = new \League\Fractal\Manager();
        $resource = new \League\Fractal\Resource\Item($data, new $responseHandler);
        return $fractal->createData($resource)->toArray();
    }

    static function handlingResponseCollectionPginate($data, $responseHandler) {

        $collection = $data->getCollection();
        $fractal = new \League\Fractal\Manager();
        $resource = new \League\Fractal\Resource\Collection($collection, new $responseHandler);
        $resource->setPaginator(new \League\Fractal\Pagination\IlluminatePaginatorAdapter($data));
        return $fractal->createData($resource)->toArray();
    }

    static function getColumnName($mapingColumns, $requestInput) {
        if (is_array($mapingColumns) && isset($mapingColumns[$requestInput]['column'])) {
            return $mapingColumns[$requestInput]['column'];
        }
        return $requestInput;
    }

    static function validateData($mapingColumns, $requestData) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Validate data", "action_startData" => json_encode($mapingColumns) . json_encode($requestData)]);
        $requestValidationRules = [];
        $status = TRUE;
        $data = [];
        if (is_array($mapingColumns)) {
            foreach ($mapingColumns as $dataName => $oneColumn) {
                if (isset($oneColumn['validation']) && strlen($oneColumn['validation']) > 0) {
                    $requestValidationRules[$dataName] = $oneColumn['validation'];
                }
            }
            $validator = \Validator::make($requestData, $requestValidationRules);
            if ($validator->fails()) {
                $status = FALSE;
                $data = $validator->errors()->toArray();
            }
        }
        return ['status' => $status, 'data' => $data];
    }

    static function validateUpdateUserData($mapingColumns, $requestData) {
        $requestValidationRules = [];
        $status = TRUE;
        $data = [];
        if (is_array($mapingColumns)) {
            foreach ($mapingColumns as $dataName => $oneColumn) {
                if (isset($oneColumn['validation']) && strlen($oneColumn['validation']) > 0) {
                    $requestValidationRules[$dataName] = $oneColumn['validation'];
                }
            }
            $validator = \Validator::make($requestData, $requestValidationRules);
            if ($validator->fails()) {
                $status = FALSE;
                $data = $validator->errors()->toArray();
            }

            if (isset($requestData["userPhoneNumber"])) {
                $checkPhone = \OlaHub\UserPortal\Models\UserModel::where("mobile_no", $requestData["userPhoneNumber"])
                                ->where("id", "!=", app("session")->get("tempID"))->first();
                if ($checkPhone) {
                    $status = FALSE;
                    $data["userPhoneNumber"][] = "validation.uniquePhone";
                }
            }

            if (isset($requestData["userEmail"])) {
                $checkEmail = \OlaHub\UserPortal\Models\UserModel::where("email", $requestData["userEmail"])
                                ->where("id", "!=", app("session")->get("tempID"))->first();
                if ($checkEmail) {
                    $status = FALSE;
                    $data["userEmail"][] = "validation.uniqueEmail";
                }
            }
        }
        return ['status' => $status, 'data' => $data];
    }

    static function getRequest($request) {
        $return = [
            'requestData' => [],
            'requestFilter' => [],
            'requestCart' => [],
            'requestSort' => [],
        ];
        if (env('REQUEST_TYPE') == 'postMan') {
            $req = $request->all();
            $return['requestData'] = isset($req['data']) ? $req['data'] : [];
            $return['requestFilter'] = isset($req['filter']) ? $req['filter'] : [];
            $return['requestCart'] = isset($req['cart']) ? $req['cart'] : [];
            $return['requestSort'] = isset($req['order']) ? $req['order'] : [];
        } else {
            $return['requestData'] = $request->json('data');
            $return['requestFilter'] = $request->json('filter');
            $return['requestCart'] = $request->json('cart');
            $return['requestSort'] = $request->json('order');
        }

        $log = new \OlaHub\UserPortal\Helpers\LogHelper();
        $log->setLogSessionData(['request_data' => json_encode($return)]);

        return $return;
    }

    static function handlingRequestFilter($filters, $maping) {
        $return['main'] = [];
        $return['relations'] = [];
        if (isset($filters['categorySlug']) && strlen($filters['categorySlug']) > 0 && isset($filters['categories']) && count($filters['categories']) > 0) {
            $categoriesData = $filters['categories'];
            for ($i = 0; $i < count($categoriesData); $i++) {
                if (isset($filters['categories'][$i]) && $filters['categories'][$i] <= 0) {
                    unset($filters['categories'][$i]);
                }
            }
            if (count($filters['categories']) > 0) {
                unset($filters['categorySlug']);
            }
        }
        foreach ($filters as $input => $value) {
            if (isset($maping[$input]) && $value) {
                $column = $maping[$input]['column'];
                $returnedValues = OlaHubCommonHelper::handlingParentValues($column, $value, $filters);
                if (isset($maping[$input]['relation']) && $maping[$input]['relation']) {
                    if (isset($returnedValues['main'])) {
                        $return['main'][$returnedValues['main']['column']] = $returnedValues['main']['values'];
                    } else {
                        $return['relations'][$maping[$input]['relation']][$column] = $returnedValues;
                    }
                } elseif ($returnedValues) {
                    $return['main'][$column] = $returnedValues;
                }
            }
        }
        return $return;
    }

    static function handlingParentValues($column, $value, $filters) {
        if ($column == 'category_id' && $value > 0) {
            return \OlaHub\UserPortal\Models\ItemCategory::getIDsByID($value);
        } elseif ($column == "category_slug" && count($filters["categories"]) <= 0) {
            return \OlaHub\UserPortal\Models\ItemCategory::getIDsBySlug($value);
        }

        return $value;
    }

    static function createNumberPrefix($number, $count = 3, $prefix = '0') {
        $return = '';
        $numberLen = strlen((string) $number);
        $finalCount = $count - $numberLen;
        for ($i = 0; $i < $finalCount; $i++) {
            $return .= $prefix;
        }
        return $return . $number;
    }

    static function sendEmail($email, $replace, $with, $template) {
        (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Prepare sending Email", "action_startData" => json_encode($email) . json_encode($replace) . json_encode($with) . $template]);
        $sendMail = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendMail) {
            $sendMail->template_code = $template;
            $sendMail->replace = $replace;
            $sendMail->replace_with = $with;
            $sendMail->to = $email;
            $sendMail->send();
            (new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "Email sent successfully"]);
        }
    }

    static function sendSms($email, $replace, $with, $template) {
        //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_name" => "Prepare sending SMS", "action_startData" => json_encode($email) . json_encode($replace) . json_encode($with) . $template]);
        $sendSms = new \OlaHub\UserPortal\Libraries\OlaHubNotificationHelper();
        if ($sendSms) {
            $sendSms->type = 'sms';
            $sendSms->template_code = $template;
            $sendSms->replace = $replace;
            $sendSms->replace_with = $with;
            $sendSms->to = $email;
            $sendSms->send();
            //(new \OlaHub\UserPortal\Helpers\LogHelper)->setActionsData(["action_endData" => "SMS sent successfully"]);
        }
    }

    static function rightPhoneNoJO($phonenum) {
        if (substr($phonenum, 0, 7) === "0096207") {
            return str_replace("0096207", "009627", $phonenum);
        } elseif (substr($phonenum, 0, 5) === "00962") {
            return $phonenum;
        } elseif (substr($phonenum, 0, 6) === "+96207") {
            return str_replace("+96207", "+9627", $phonenum);
        } elseif (substr($phonenum, 0, 4) === "+962") {
            return str_replace("+", "00", $phonenum);
        } elseif (substr($phonenum, 0, 2) === "07") {
            $phoneTemp = str_split($phonenum, 1);
            unset($phoneTemp[0]);
            $newPhonenum = implode("", $phoneTemp);
            return "00962" . $newPhonenum;
        } elseif (substr($phonenum, 0, 1) === "7") {
            return "00962" . $phonenum;
        } else {
            return $phonenum;
        }
    }

    static function getWordsFromString($string, $count = 50) {
        if (!is_string($string)) {
            return $string;
        }

        $words = str_word_count(strip_tags($string), 1);
        $returnWord = [];
        if (count($words) > $count) {
            for ($i = 0; $i < $count; $i++) {
                if (isset($words[$i])) {
                    $returnWord[] = $words[$i];
                }
            }
        } else {
            $returnWord = $words;
        }
        $return = implode(" ", $returnWord) . " ...";
        return $return;
    }

    static function getUserBrowserAndOS($userAgent) {

        if (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'MAC';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/application/i', $userAgent)) {
            $platform = 'OlaHub application';
        }else{
            $platform = 'unkwon platform';
        }

        if (preg_match('/MSIE/i', $userAgent) && !preg_match('/Opera/i', $userAgent)) {
            $bname = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $bname = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $bname = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $bname = 'Apple Safari';
        } elseif (preg_match('/Opera/i', $userAgent)) {
            $bname = 'Opera';
        } elseif (preg_match('/Netscape/i', $userAgent)) {
            $bname = 'Netscape';
        } elseif (preg_match('/application/i', $userAgent)) {
            $bname = '';
        }else{
            $bname = 'unkwon browser';
        }

        return "$platform - $bname";
    }

    static function timeElapsedString($datetime, $full = false) {

        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    static function setPayUsed($bill) {
        $payByData = $bill->paid_by;
        $return = [];
        $return["paidBy"] = "";
        $payBy = explode("_", $payByData);
        if ($bill->voucher_used > 0) {
            $return["paidBy"] .= "Voucher";
            $return["orderPayVoucher"] = $bill->voucher_used;
            $return["orderVoucherAfterPay"] = $bill->voucher_after_pay;
        }
        if ($bill->voucher_used != $bill->billing_total) {
            foreach ($payBy as $payType) {
                if ($payType > 0) {
                    $payData = \OlaHub\UserPortal\Models\PaymentMethod::find($payType);
                    if ($payByData) {
                        if (strlen($return["paidBy"]) > 0) {
                            $return["paidBy"] .= " & ";
                        }
                        $return["orderPayByGate"] = OlaHubCommonHelper::returnCurrentLangField($payData, "name");
                        $return["paidBy"] .= OlaHubCommonHelper::returnCurrentLangField($payData, "name");
                        $return["orderPayByGateAmount"] = $bill->billing_total - $bill->voucher_used;
                    }
                }
            }
        }
        return $return;
    }

//    static function setPayUsed($bill) {
//        $payBy = $bill->paid_by;
//        $return = [];
//        $return["paidBy"] = "";
//        if ($bill->voucher_used > 0) {
//            
//        }
//        if ($bill->voucher_used != $bill->billing_total) {
//            if (strlen($return["paidBy"]) > 0) {
//                $return["paidBy"] .= " & ";
//            }
//            $payBy = preg_replace('/[0-9]+_/', '', $payBy);
//            $return["orderPayByGate"] = OlaHubCommonHelper::getpaymentName($payBy);
//            $return["paidBy"] .= OlaHubCommonHelper::getpaymentName($payBy);
//            $return["orderPayByGateAmount"] = $bill->billing_total - $bill->voucher_used;
//        }
//        return $return;
//    }

    static function getpaymentName($pay) {
        $payDecoded = (array) json_decode(str_replace("\u", "", $pay));
        $payment = isset($payDecoded["en"]) ? str_replace(["_"], " ", $payDecoded["en"]) : str_replace(["_"], " ", $pay);
        $paymentName = \OlaHub\UserPortal\Models\PaymentMethod::where('name', 'LIKE', "%$payment%")->first();
        if ($paymentName) {
            return OlaHubCommonHelper::returnCurrentLangField($paymentName, "name");
        }
        return $payment;
    }

    static function getTranslatedCurrency($currency) {
        $languageArray = explode("_", app('session')->get('def_lang')->default_locale);
        $language = strtolower($languageArray[0]);
        if ($language == "ar") {
            return "د.أ.";
        } else {
            return $currency;
        }
    }

    static function checkHolidaysDatesNumber($totalDays) {
        $returnDates = $totalDays;
        for ($i = 2; $i <= $totalDays + 1; $i++) {
            $timeStamp = strtotime("+$i Days");
            $day = date("N", $timeStamp);
            if (in_array($day, WEEK_END_DATES)) {
                $returnDates++;
            }
        }
        $checkTotal = strtotime("+$returnDates Days");
        $day = date("N", $checkTotal);
        if($day == 6){
            $returnDates += 2;
        }elseif($day == 7){
            $returnDates ++;
        }
        return $returnDates;
    }

}
