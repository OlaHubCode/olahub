<?php

namespace OlaHub\UserPortal\Middlewares;

use Closure;
use OlaHub\UserPortal\Models\Country;
use OlaHub\UserPortal\Models\Language;

class LocaleMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request 
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $defLang = FALSE;
        $country = Country::where('two_letter_iso_code', env('DEFAULT_COUNTRY_CODE', 'JO'))->first();
        $defCountry = $country;

        $countryHeader = $request->headers->get('country');
        if ($countryHeader && strlen($countryHeader) == 2) {
            $country = Country::where('two_letter_iso_code', $countryHeader)->where('is_supported', '1')->where('is_published', '1')->first();
            if ($country) {
                $defCountry = $country;
            }
        } else {
            $getIPInfo = new \OlaHub\UserPortal\Helpers\getIPInfo();
            $countryCode = $getIPInfo->ipData('countrycode');
            if ($countryCode && strlen($countryCode) == 2) {
                $country = Country::where('two_letter_iso_code', $countryCode)->where('is_supported', '1')->where('is_published', '1')->first();
                if ($country) {
                    $defCountry = $country;
                }
            }
        }


        $languageCode = $request->headers->get('language'); //explode('_', $request->headers->get('language'))[0];
        if ($languageCode) {
            $language = Language::where('default_locale', $languageCode)->first();
            if ($language) {
                $defLang = $language; //explode('_', $language->default_locale)[0];
            }
        }

        if (!$defLang) {
            $language = Language::find($defCountry->language_id);
            if ($language) {
                $defLang = $language; //explode('_', $language->default_locale)[0];
            }
        }
        app('session')->put('def_lang', $defLang);
        app('session')->put('def_country', $defCountry);
        app('session')->put('def_currency', $defCountry->currencyData);
        return $next($request);
    }

}
