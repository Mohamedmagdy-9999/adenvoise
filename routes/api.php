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



Route::prefix('v-admin')->group(function () {
    
   
    Route::post('admin_login', 'AuthApiController@admin_login');
        Route::middleware(['auth:api_admins', 'admin'])->group(function () {

            Route::post('update_admin_profile', 'AuthApiController@update_admin_profile');
            Route::get('complaints', 'AdminApiController@complaints');

            Route::get('directorates','AdminApiController@directorates');
            Route::get('neighborhoods/{id}','AdminApiController@neighborhood');
            Route::get('types','AdminApiController@types');
            Route::get('levels','AdminApiController@levels');
            Route::get('categories','AdminApiController@categories');

            Route::get('sliders', 'AdminApiController@sliders');
            Route::post('add_slider', 'AdminApiController@add_slider');
            Route::post('update_slider/{id}', 'AdminApiController@update_slider');
            Route::delete('delete_slider/{id}', 'AdminApiController@delete_slider');


            Route::post('add_blog', 'AdminApiController@add_blog');
            Route::post('update_blog/{id}', 'AdminApiController@update_blog');
            Route::delete('delete_blog/{id}', 'AdminApiController@delete_blog');
            Route::get('blogs', 'AdminApiController@blogs');
            
        });

});
