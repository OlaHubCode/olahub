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
    $router->post('logout', 'OlaHubUserController@logoutUser');
    $router->post('getUser', 'OlaHubUserController@getUserInfo');
    $router->post('getProfile', 'OlaHubUserController@getProfileInfo');
    $router->post('friends', 'OlaHubUserController@getUserFriends');
    $router->post('requests', 'OlaHubUserController@getUserRequests');
    $router->post('responses', 'OlaHubUserController@getUserResponses');
    $router->post('voucherBalance', 'OlaHubUserController@getUservoucherData');
    $router->post('updateUser', 'OlaHubUserController@updateUserData');
    $router->post('verifiedupdateUser', 'OlaHubUserController@verifiedUpdateUserData');
    $router->post('headerInfo', 'OlaHubUserController@getHeaderInfo');
    $router->post('getUserPurchasedItems', 'PurchasedItemsController@getUserPurchasedItems');
    $router->post('uploadProfilePhoto', 'OlaHubUserController@uploadUserProfilePhoto');
    $router->post('uploadCoverPhoto', 'OlaHubUserController@uploadUserCoverPhoto');
    $router->post('interests', 'OlaHubUserController@getAllInterests');
});

$router->post('registration', 'OlaHubGuestController@registerUser');
$router->post('checkActive', 'OlaHubGuestController@checkActiveCode');
$router->post('resendCode', 'OlaHubGuestController@resendActivationCode');
$router->post('checkSecureActive', 'OlaHubGuestController@checkSecureActive');
$router->post('resendSecureCode', 'OlaHubGuestController@resendSecureCode');
$router->post('login', 'OlaHubGuestController@login');
$router->post('loginFacebook', 'OlaHubGuestController@loginWithFacebook');
$router->post('loginGoogle', 'OlaHubGuestController@loginWithGoogle');
$router->post('forgetPassword', 'OlaHubGuestController@forgetPasswordUser');
$router->post('changePassword', 'OlaHubGuestController@resetGuestPassword');
$router->post('allInterests', 'OlaHubGuestController@getAllInterests');
$router->post('sec_log_action/{id}', 'OlaHubGuestController@loginAsUser');
