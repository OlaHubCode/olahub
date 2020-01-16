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
    'middleware' => ['checkAuth'],
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('/{type:\bdefault|event|celebration\b}/{first}', 'OlaHubCartController@getList');
    $router->post('/{type:\bdefault|event|celebration\b}', 'OlaHubCartController@getList');
    
    $router->post('/gift_friend', 'OlaHubCartController@setDefaultCartToBeGift');
    $router->post('/gift_date', 'OlaHubCartController@setCartToBeGiftDate');
    $router->post('/gift_details', 'OlaHubCartController@getDefaultCartGiftDetails');
    $router->post('/gift_cancel', 'OlaHubCartController@cancelDefaultCartToBeGift');
    $router->post('/changeCountry/{id:[0-9]+}', 'OlaHubCartController@setNewCountryForDefaultCart');
    $router->post('add/{itemType:\bstore|designer\b}/{type:\bdefault|event|celebration\b}', 'OlaHubCartController@newCartItem');
    $router->put('update/{itemType:\bstore|designer\b}/{type:\bdefault|event|celebration\b}', 'OlaHubCartController@newCartItem');
    $router->delete('remove/{type:\bdefault|event|celebration\b}/{itemType:\bstore|designer\b}', 'OlaHubCartController@removeCartItem');
    $router->post('uploadGiftVideo', 'OlaHubCartController@uploadGiftVideo');

});
$router->group([
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('/getCartItems', 'OlaHubCartController@getNotLoginCartItems');
    $router->post('total/{type:\bdefault|event\b}', 'OlaHubCartController@getCartTotals');
    $router->post('saveCookie', 'OlaHubCartController@saveCookie');
});