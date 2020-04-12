<?php

$router->group([
    'middleware' => ['checkAuth'],
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('0', 'OlaHubSharesController@newSharedItemsUser');
    $router->post('1', 'OlaHubSharesController@removeSharedItemsUser');
});