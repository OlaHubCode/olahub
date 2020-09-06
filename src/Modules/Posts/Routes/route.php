<?php

/**
 * MerBankInfos routes
 * Handling URL requests with method type to send to Controller
 *
 * @copyright (c) 2018, OlaHub LLC
 * @version 1.0.0
 */
$router->group([
//    'middleware' => ['checkAuth'],
    'prefix' => basename(strtolower(dirname(__DIR__)))
        ], function () use($router) {
    $router->post('/', 'OlaHubPostController@getPosts');
    $router->post('{type:\bgroup|friend\b}', 'OlaHubPostController@getPosts');
    $router->post('add', 'OlaHubPostController@addNewPost');
    $router->post('likePost', 'OlaHubPostController@likePost');
//    $router->post('sharePost', 'OlaHubPostController@sharePost');
    $router->post('sharePost', 'OlaHubPostController@newSharePost');
    $router->post('removeSharePost', 'OlaHubPostController@removeSharePost');
    $router->post('addComment', 'OlaHubPostController@addNewComment');
    $router->post('getComments', 'OlaHubPostController@getPostComments');
    $router->post('addReply', 'OlaHubPostController@addNewReply');
    $router->post('EditComment', 'OlaHubPostController@updateComment');
    $router->delete('deleteComment', 'OlaHubPostController@deleteComment');
    $router->post('onePost', 'OlaHubPostController@getOnePost');
    $router->delete('deletePost', 'OlaHubPostController@deletePost');
    $router->put('updatePost', 'OlaHubPostController@updatePost');
    $router->POST('usersLike', 'OlaHubPostController@usersLike');
    $router->post('hashTag', 'OlaHubPostController@hashPost');
    $router->post('ReportPost', 'OlaHubPostController@ReportPost');
    $router->get('getTophashTags', 'OlaHubPostController@getTophashTags');
    $router->post('votersOnPost','OlaHubPostController@votersOnPost');
    $router->put('updatePrivacyPost', 'OlaHubPostController@updatePrivacyPost');

});
