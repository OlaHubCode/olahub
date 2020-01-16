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
    
    $router->group([
        'middleware' => ['checkAuth']
            ], function () use($router) {
    
    
    $router->post('/calendar', 'FriendController@listFriendCalendar');
    $router->post('/wishList', 'FriendController@listFriendWishList');
    $router->post('/info', 'FriendController@getProfileInfo');
    $router->post('/sendFriend', 'FriendController@sendFriendRequest');
    $router->post('/upComingEvent', 'FriendController@listUserUpComingEvent');
    $router->post('/sendFriend', 'FriendController@sendFriendRequest');
    $router->post('/cancelFriend', 'FriendController@cancelFriendRequest');
    $router->post('/rejectFriend', 'FriendController@rejectFriendRequest');
    $router->post('/acceptFriend', 'FriendController@acceptFriendRequest');
    $router->post('/removeFriend', 'FriendController@removeFriend');
    
    });
});