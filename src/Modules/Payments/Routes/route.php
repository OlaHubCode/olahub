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
        ], function () use($router) {
    $router->post(basename(strtolower(dirname(__DIR__))). "/{type:\bdefault|event|celebration\b}", 'OlaHubPaymentsMainController@getPaymentsList');
    $router->post('prepareBilling/{type:\bdefault|event|celebration\b}', 'OlaHubPaymentsPrepareController@createUserBilling');
});
$router->post('callbackBilling', 'OlaHubPaymentsCallbackController@callbackUserBilling');
