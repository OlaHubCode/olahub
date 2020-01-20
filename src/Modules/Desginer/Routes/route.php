<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 * 
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */
$router->group([
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->get('assignedCountries', 'OlaHubDesginerController@getAllAssignedToFranchiseCountries');
    $router->get('parentCategories', 'OlaHubDesginerController@getAllParentCategories');
    $router->get('classifications', 'OlaHubDesginerController@getAllClassifications');
    $router->post('newDesginer', 'OlaHubDesginerController@addNewDesginer');
});

