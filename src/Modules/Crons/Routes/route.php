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
    $router->get('/publishCelebration', 'CronController@publishCelebration');
    $router->get('/publishOneCelebration/{id:[0-9]+}', 'CronController@publishSpecificCelebration');
    $router->get('/scheduleCelebration', 'CronController@scheduleCelebration');
    $router->get('/cancelCelebrationPayment', 'CronController@autoCancelPayment');
    $router->get('/check_gifts', 'CronController@sendGiftsToTarget');
    $router->get('/communities_updates', 'CronController@communitiesAddData');
    $router->get('/posts_updates', 'CronController@postsAddData');
    $router->get('/update_users_numbers', 'CronController@usersPhoneNumbersChange');
    $router->get('/create_merchant_accounts', 'CronController@merchantsUserAccounts');
    $router->get('/checkPendingPays', 'CronController@pendingPaysActions');
    $router->get('/checkMembers', 'CronController@updateCommunitiesTotalMembers');
    $router->get('/xiaomi', 'CronController@updateXiaomiItem');
    $router->get('/updateCountries', 'CronController@updateCountriesCode');
    $router->get('/updateItem/{slug}', 'CronController@updateItemSlugUnique');
});
