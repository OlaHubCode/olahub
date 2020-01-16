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
    $router->post('/', 'OlaHubPostController@getPosts');
    $router->post('{type:\bgroup|friend\b}', 'OlaHubPostController@getPosts');
    $router->post('add', 'OlaHubPostController@addNewPost');
    $router->post('addComment', 'OlaHubPostController@addNewComment');
    $router->post('getComments', 'OlaHubPostController@getPostComments');
    $router->post('addReply', 'OlaHubPostController@addNewReply');
    $router->post('onePost', 'OlaHubPostController@getOnePost');
    $router->delete('deletePost', 'OlaHubPostController@deletePost');
    $router->put('updatePost', 'OlaHubPostController@updatePost');
});