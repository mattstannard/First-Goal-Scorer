<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('api/db_test', [
    'as' => 'api.db_test', 'uses' => 'APIController@testDBConnection'
]);

$router->get('api/log', [
    'as' => 'api.log', 'uses' => 'APIController@logEntry'
]);

$router->get('api/minute', [
    'as' => 'api.minute', 'uses' => 'APIController@processInboundMinute'
]);

$router->get('fixture/{fixtureId}', [
    'as' => 'api.minute', 'uses' => 'APIController@showFixture'
]);

