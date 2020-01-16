<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */
$di = scandir(__DIR__ . '/../Modules');
foreach ($di as $child) {
    $file = __DIR__ . "/../Modules/$child/Routes/route.php";
    if ($child != '.' && $child != '..' && file_exists($file)) {
        require $file;
    }
}

$router->group([], function () use($router) {
    $router->post('countries', 'OlaHubGeneralController@getAllCountries');
    $router->post('list_countries', 'OlaHubGeneralController@getAllListedCountries');
    $router->post('interests', 'OlaHubGeneralController@getAllInterests');
    $router->post('home/communities', 'OlaHubGeneralController@getAllCommuntites');
    $router->post('olahub/communities', 'OlaHubGeneralController@getOlaHubCommuntites');
    $router->post('countries_code', 'OlaHubGeneralController@getCodeCountries');
    $router->get('getUserCountry', 'OlaHubGeneralController@checkUserCountry');
    $router->post('sellWithUs', 'OlaHubGeneralController@sendSellWithUsEmail');
    $router->post('search_general', 'OlaHubGeneralController@searchAll');
    $router->post('search_filters', 'OlaHubGeneralController@searchAllFilters');
    $router->post('social', 'OlaHubGeneralController@getSocialAccounts');
    $router->post('allCountries', 'OlaHubGeneralController@getAllUnsupportCountries');
    $router->post('setStatistics/{getFrom:\bc|saif|farah\b}', 'OlaHubGeneralController@setAdsStatisticsData');
    $router->get('page/{type:\bterms|payment|privacy|contact\b}', 'OlaHubGeneralController@getStaticPage');
    $router->group([
        'middleware' => ['checkAuth'],
            ], function () use($router) {
        $router->post('search_user', 'OlaHubGeneralController@searchUsers');
        $router->post('invite', 'OlaHubGeneralController@inviteNewUser');
        $router->post('timeline', 'OlaHubGeneralController@getUserTimeline');
        $router->post('notification', 'OlaHubGeneralController@getUserNotification');
        $router->post('readNotification', 'OlaHubGeneralController@readNotification');
        $router->post('getAllNotifications', 'OlaHubGeneralController@getAllNotifications');
        $router->post('shareItem', 'OlaHubGeneralController@shareNewItem');
        $router->post('checkUserMerchant', 'OlaHubGeneralController@checkUserMerchant');
        $router->post('getCitiesByRegion/{regionId}','OlaHubGeneralController@getCities');
        $router->post('follow/{type:\bbrands|occassions|designers|interests\b}/{id:[0-9]+}', 'OlaHubGeneralController@userFollow');
        $router->post('unfollow/{type:\bbrands|occassions|designers|interests\b}/{id:[0-9]+}', 'OlaHubGeneralController@userUnFollow');
        $router->post('listFollowing', 'OlaHubGeneralController@listUserFollowing');

    });
});
