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
        
        $router->post('/list', 'MainController@listGroups');
        $router->post('/list/all', 'MainController@listAllGroups');
        $router->post('/create', 'MainController@createNewGroup');
        $router->post('/one', 'MainController@getOneGroup');
        $router->put('/update', 'MainController@updateGroup');
        $router->delete('/delete', 'MainController@deleteGroup');
        $router->post('/invite', 'MainController@inviteUserToGroup');
        $router->post('/adminApprove', 'MainController@approveAdminGroupRequest');
        $router->post('/adminCancelInvite', 'MainController@cancelAdminInvite');
        $router->post('/userApprove', 'MainController@approveUserGroupRequest');
        $router->delete('/removeMember', 'MainController@removeGroupMember');
        $router->delete('/adminReject', 'MainController@rejectAdminGroupRequest');
        $router->delete('/userReject', 'MainController@rejectUserGroupRequest');
        $router->post('/members', 'MainController@listGroupMembers');
        $router->post('/leave', 'MainController@leaveGroup');
        $router->post('/join', 'MainController@joinPublicGroup');
        $router->post('/requestJoin', 'MainController@joinClosedGroup');
        $router->post('/cancelRequestJoin', 'MainController@cancelJoinClosedGroup');
        $router->post('/uploadImage', 'MainController@uploadGroupImageAndCover');
        $router->post('/relatedMerchant', 'MainController@getBrandsRelatedGroupInterests');
        $router->post('/relatedDesigners', 'MainController@getDesignersRelatedGroupInterests');
        
        $router->post('/approvePost', 'MainController@approveAdminPost');
        $router->post('/rejectPost', 'MainController@rejectGroupPost');
        $router->post('/listPendingPost', 'MainController@listPendingGroupPost');
    
    });
    
});