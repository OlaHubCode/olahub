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
    $router->post('/', 'OlaHubWishListsController@getList');
    $router->post('0', 'OlaHubWishListsController@newWishListUser');
    $router->post('delete/{id:[0-9]+}', 'OlaHubWishListsController@removeItemFromWishlist');
    $router->post('1', 'OlaHubWishListsController@removeWishListUser');
    $router->post('/occasions', 'OlaHubWishListsController@getWishlistOccasions');
    $router->delete('delete/{id:[0-9]+}', 'OlaHubWishListsController@deleteItemFromWishlistById');
});