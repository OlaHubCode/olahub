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
    $router->post('/', 'OlaHubLikesController@getList');
    $router->post('0', 'OlaHubLikesController@newLikedItemsUser');
    $router->post('1', 'OlaHubLikesController@removeLikedItemsUser');
});