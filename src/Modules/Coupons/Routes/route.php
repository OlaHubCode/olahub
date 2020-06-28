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
        ], function () use($router) {
    $router->post("coupons", 'OlaHubCouponsController@checkCouponForUser');
});