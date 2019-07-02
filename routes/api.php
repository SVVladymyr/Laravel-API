<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([

    'middleware' => 'api',
    'prefix' => 'v1'

], function ($router) {

    /**
     * Routes fot Users logic
     */
    Route::get('token', 'API\AuthController@token');
    Route::post('users', 'API\AuthController@register');
    Route::get('users', 'API\AuthController@pagination');
    Route::get('users/{id}', 'API\AuthController@getUser');
    Route::post('login', 'API\AuthController@login');
    Route::post('logout', 'API\AuthController@logout');

    /**
     * Routes fot Positions logic
     */
    Route::get('positions', 'API\PositionController@index');
});