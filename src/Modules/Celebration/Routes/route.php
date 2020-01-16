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
        
        $router->post('newCelebration', 'CelebrationController@createNewCelebration');
        $router->post('newCelebrationByCalendar', 'CelebrationController@createCelebrationByCalendar');
        $router->post('newCelebrationByCart', 'CelebrationController@createCelebrationByCart');
        $router->post('updateCelebration', 'CelebrationController@updateCelebration');
        $router->post('deleteCelebration', 'CelebrationController@deleteCelebration');
        $router->post('list', 'CelebrationController@ListCelebrations');
        $router->post('one', 'CelebrationController@getOneCelebration');
        $router->post('userShippingAddress', 'CelebrationController@getUserShippingAddress');
        $router->post('creatorShippingAddress', 'CelebrationController@getCreatorShippingAddress');
        
        
        
        $router->post('newParticipant', 'ParticipantController@createNewParticipant');
        $router->post('deleteParticipant', 'ParticipantController@deleteParticipant');
        $router->post('approveCelebration', 'ParticipantController@approveParticipantRequest');
        $router->post('rejectCelebration', 'ParticipantController@rejectParticipantRequest');
        $router->delete('leaveCelebration', 'ParticipantController@leaveCelebration');
        $router->post('listParticipants', 'ParticipantController@ListCelebrationParticipants');
        
        $router->post('newGift', 'GiftController@addGiftToCelebration');
        //$router->post('deleteGift', 'GiftController@deleteGiftFromCelebration');
        $router->post('listGifts', 'GiftController@listCelebrationGifts');
        $router->post('likeGift', 'GiftController@likeCelebrationGift');
        $router->delete('deleteGift', 'GiftController@deleteCelebrationGift');
        
        $router->post('commitCelebration', 'GiftController@commitCelebration');
        $router->post('unCommitCelebration', 'GiftController@unCommitCelebration');
        
        $router->post('addWishText', 'CelebrationContentsController@addParticipantWishText');
        $router->post('addvideo', 'CelebrationContentsController@uploadCelebrationVideo');
        $router->post('schedule', 'CelebrationContentsController@scheduleCelebration');
        $router->post('changeDate', 'CelebrationContentsController@changeDateBeforeSchedule');
        $router->post('uploadMediaToPublishedCelebration', 'CelebrationContentsController@uploadMediaTopublishedCelebration');
        
    });
});

