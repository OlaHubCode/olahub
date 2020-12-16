<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */
$router->group([
    'middleware' => ['checkAuth'],
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('/', 'PurchasedItemsController@getUserPurchasedItems');
    $router->post('cancelation/{id:[0-9]+}', 'PurchasedItemsController@cancelPurshasedItem');
    $router->post('refund/{id:[0-9]+}', 'PurchasedItemsController@refundPurshasedItem');

});
$router->group([
    'prefix' => basename(strtolower(dirname(__DIR__)))
], function () use ($router) {
    $router->post('getRatingItems', 'PurchasedItemsController@getNotRatingBillingItems');
});