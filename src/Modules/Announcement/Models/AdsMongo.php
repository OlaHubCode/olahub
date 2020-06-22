<?php

namespace OlaHub\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class AdsMongo extends Eloquent {

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $collection = 'releasedAds';
    protected $connection = 'mongo';

    static function setAdView($camaign) {
        $request = \Illuminate\Http\Request::capture();
        $userIP = $request->ip();
        $userData = explode(' - ', \OlaHub\UserPortal\Helpers\OlaHubCommonHelper::getUserBrowserAndOS($request->userAgent()));
        $browser = $userData[1];
        $platform = $userData[0];
        $camaign->total_reach++;
        $oldView = AdStatistics::where(function($q) use($camaign, $userIP, $browser, $platform) {
                    $q->where('campign_id', $camaign->id)
                    ->where('user_ip', $userIP)
                    ->where('user_browser', $browser)
                    ->where('user_os', $platform)
                    ->where('user_country', app('session')->get('def_country')->id);
                })
                ->orWhere('user_id',app('session')->get('tempID'))
                ->first();
        if (!$oldView) {
            $oldView = new AdStatistics;
            $oldView->campign_id = $camaign->id;
            $oldView->user_ip = $userIP;
            $oldView->user_browser = $browser;
            $oldView->user_os = $platform;
            $oldView->view_at = date('Y-m-d h:i:s');
            $oldView->user_country = app('session')->get('def_country')->id;
            if(app('session')->get('tempID')){
                $oldView->user_id = app('session')->get('tempID');
            }
            $oldView->save();
            $camaign->total_views++;
        }
        $camaign->save();
    }

}
