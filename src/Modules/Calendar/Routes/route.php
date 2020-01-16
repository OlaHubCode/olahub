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
    
    
    $router->post('/ListCalendars', 'CalendarController@ListAllCalendars');
    $router->post('/addCalendar', 'CalendarController@createNewCalendar');
    $router->delete('/deleteCalendar/{id}', 'CalendarController@deleteUserCalendar');
    $router->post('/updateCalendar/{id}', 'CalendarController@updateUserCalendar');
    $router->post('/one', 'CalendarController@getOneCalendar');
    $router->get('/PrerequestOccassion', 'CalendarController@getAllOccassionByCountry');
    
    });
    
});