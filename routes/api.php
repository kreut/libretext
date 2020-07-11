<?php

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

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', 'Auth\UserController@current');

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');

    //student
    Route::get('/assignments', 'AssignmentController@index');

    //instructor
    Route::get('/courses', 'CourseController@index');
    Route::get('/courses/{course}/grades', 'GradeController@index');
    Route::get('/courses/{course}/assignments', 'AssignmentController@index');


    Route::post('/courses', 'CourseController@store');
    Route::post('/courses/{course}/assignments', 'AssignmentController@store');
    Route::post('/courses/{course}/assignments/{assignment}', 'AssignmentController@update');
    Route::post('/courses/{course}', 'CourseController@update');

    Route::delete('/courses/{course}', 'CourseController@destroy');
    Route::delete('/courses/{course}/assignments/{assignment}', 'AssignmentController@destroy');

    Route::get('/courses', 'CourseController@index');
    Route::get('/h5p', 'H5pController@index');

});

Route::group(['middleware' => 'guest:api'], function () {

    Route::post('login', 'Auth\LoginController@login');
    Route::post('register', 'Auth\RegisterController@register');

    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend');

    Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');
});
