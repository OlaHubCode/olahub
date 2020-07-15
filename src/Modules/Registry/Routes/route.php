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

    $router->group([
        'middleware' => ['checkAuth']
            ], function () use($router) {
        
        $router->post('newRegistry', 'RegistryController@createNewRegistry');
        $router->post('updateRegistry', 'RegistryController@updateRegistry');
        $router->post('deleteRegistry', 'RegistryController@deleteRegistry');
        $router->get('list', 'RegistryController@ListRegistry');
        $router->post('one', 'RegistryController@getOneRegistry');


        $router->post('newParticipants', 'RegistryParticipantController@createParticipants');
        $router->post('deleteParticipant', 'RegistryParticipantController@deleteParticipant');
        $router->post('listParticipants', 'RegistryParticipantController@ListRegistryParticipants');
        $router->post('inviteNotRegisterUsers', 'RegistryParticipantController@inviteNotRegisterUsers');

        $router->post('newGift', 'RegistryGiftController@newGift');
        $router->post('listGifts', 'RegistryGiftController@ListRegistryGifts');
        $router->post('deleteGift', 'RegistryGiftController@removeRegistryItem');

        
    });
});
