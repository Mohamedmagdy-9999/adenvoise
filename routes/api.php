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
            Route::get('complaint_messages/{id}', 'MobileApiController@complaint_messages');
            Route::post('send_message', 'MobileApiController@send_message');
            
            Route::get('sliders', 'MobileApiController@sliders');

           
            Route::get('blogs', 'MobileApiController@blogs');

            Route::post('add_rate_complaint','MobileApiController@add_rate_complaint');
            
        });

});



Route::prefix('v-admin')->group(function () {
    
   
    Route::post('admin_login', 'AuthApiController@admin_login');
        Route::middleware(['auth:api_admins', 'admin'])->group(function () {
            Route::get('check_admin', 'AuthApiController@admin_check');
            Route::post('update_admin_profile', 'AuthApiController@update_admin_profile');
            Route::get('complaints', 'AdminApiController@complaints');
            Route::get('complaint_details/{id}', 'AdminApiController@complaint_details');
            Route::post('update_complaint_status/{id}', 'AdminApiController@update_complaint_status');
            
            Route::get('complaint_messages/{id}', 'AdminApiController@complaint_messages');
            Route::post('admin_send_message', 'AdminApiController@admin_send_message');
            

            Route::get('directorates','AdminApiController@directorates');
            Route::get('entities','AdminApiController@entities');
            Route::get('neighborhoods/{id}','AdminApiController@neighborhood');
            Route::get('types','AdminApiController@types');
            Route::get('levels','AdminApiController@levels');
            Route::get('categories','AdminApiController@categories');
            Route::get('complaint_status','AdminApiController@complaint_status');


            Route::get('sliders', 'AdminApiController@sliders');
            Route::post('add_slider', 'AdminApiController@add_slider');
            Route::post('update_slider/{id}', 'AdminApiController@update_slider');
            Route::delete('delete_slider/{id}', 'AdminApiController@delete_slider');


            Route::post('add_blog', 'AdminApiController@add_blog');
            Route::post('update_blog/{id}', 'AdminApiController@update_blog');
            Route::delete('delete_blog/{id}', 'AdminApiController@delete_blog');
            Route::get('blogs', 'AdminApiController@blogs');

            Route::get('citizens', 'AdminApiController@citizens');
            Route::get('citizen_complaints/{id}', 'AdminApiController@citizen_complaints');

            Route::post('add_user', 'AdminApiController@add_user');
            Route::get('users', 'AdminApiController@users');


            Route::post('update_user/{id}', 'AdminApiController@update_user');
            Route::post('toggle_user_status/{id}', 'AdminApiController@toggle_user_status');

            Route::get('cards', 'AdminApiController@cards');

            Route::get('complaintsByStatus', 'AdminApiController@complaintsByStatus');
            Route::get('complaintsByDirectorate', 'AdminApiController@complaintsByDirectorate');
            Route::get('complaintsByClassification', 'AdminApiController@complaintsByClassification');
            Route::get('performance', 'AdminApiController@performance');

            Route::get('newcomplaints', 'AdminApiController@newcomplaints');
            Route::get('communityPressureAndTrendingComplaints', 'AdminApiController@communityPressureAndTrendingComplaints');
            Route::get('get_citizen_details/{id}', 'AdminApiController@get_citizen_details');
            Route::get('complaints_report', 'AdminApiController@complaints_report');
        });

});


Route::prefix('v-user')->group(function () {
    
   
    Route::post('user_login', 'AuthApiController@user_login');
        Route::middleware(['auth:api_users', 'user'])->group(function () {
            Route::get('check_user', 'AuthApiController@user_check');
            Route::post('update_user_profile', 'AuthApiController@update_user_profile');
            Route::get('complaints', 'UserApiController@complaints');
            Route::get('complaint_details/{id}', 'UserApiController@complaint_details');
            Route::post('update_complaint_status/{id}', 'UserApiController@update_complaint_status');
            
            Route::get('complaint_messages/{id}', 'UserApiController@complaint_messages');
            Route::post('user_send_message', 'UserApiController@user_send_message');
            

          

          

            // Route::get('citizens', 'AdminApiController@citizens');
            // Route::get('citizen_complaints/{id}', 'AdminApiController@citizen_complaints');


           
            // Route::get('get_citizen_details/{id}', 'AdminApiController@get_citizen_details');
        });

});
