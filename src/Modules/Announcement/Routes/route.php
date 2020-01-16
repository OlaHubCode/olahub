<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 * 
 * @author Mohamed EL-Absy <mohamed.elabsy@yahoo.com>
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */

$router->group([
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    
    $router->group([], function () use($router) {
        $router->post('homepage', 'AdsController@homePageAds');
        $router->post('homepage/slider', 'AdsController@homePageSlider');
        $router->post('homepage/holders', 'AdsController@homePagePlacehoders');
        $router->post('internal', 'AdsController@internalPageAds');
        $router->post('likeSponser', 'AdsController@likeSponserAd');
    });
});