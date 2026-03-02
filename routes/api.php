<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('v-mobile')->group(function () {
    
    Route::post('register', 'AuthApiController@add_new_citizen');
    Route::post('login', 'AuthApiController@login');
        Route::middleware(['auth:api_citizens', 'citizen'])->group(function () {

        Route::post('update_profile', 'AuthApiController@update_profile');
        Route::get('check', 'AuthApiController@check');


            Route::get('directorates','MobileApiController@directorates');
            Route::get('neighborhoods/{id}','MobileApiController@neighborhood');
            Route::get('types','MobileApiController@types');
            Route::get('levels','MobileApiController@levels');
            Route::get('categories','MobileApiController@categories');
            Route::get('complaint_types','MobileApiController@complaint_types');
            Route::post('add_complaint', 'MobileApiController@add_complaint');
            Route::get('my_complaints', 'MobileApiController@my_complaints');
            
            
            Route::get('sliders', 'MobileApiController@sliders');

           
            Route::get('blogs', 'MobileApiController@blogs');
            
        });

});

