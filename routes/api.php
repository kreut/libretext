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
Route::get('jwt-test', 'Auth\UserController@getAuthenticatedUser');

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', 'Auth\UserController@current');

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');


    Route::get('/courses', 'CourseController@index');
    Route::post('/courses', 'CourseController@store');
    Route::patch('/courses/{course}', 'CourseController@update');
    Route::delete('/courses/{course}', 'CourseController@destroy');

    Route::get('/courses/{course}/assignments', 'AssignmentController@index');
    Route::get('/assignments/{assignmentId}', 'AssignmentController@show');
    Route::post('/assignments', 'AssignmentController@store');
    Route::patch('/assignments/{assignment}', 'AssignmentController@update');
    Route::delete('/assignments/{assignment}', 'AssignmentController@destroy');


    Route::get('/scores/{course}', 'ScoreController@index');
    Route::patch('/scores/{assignment}/{user}', 'ScoreController@update');//just doing a patch here because "no score" is consider a score


    Route::get('/extensions/{assignment}/{user}', 'ExtensionController@show');
    Route::post('/extensions/{assignment}/{user}', 'ExtensionController@store');
    Route::patch('/extensions/{assignment}/{user}', 'ExtensionController@update');



    ///START
    Route::get('/tags', 'TagController@index');

    Route::post('/questions/getQuestionsByTags', 'QuestionController@getQuestionsByTags');


    Route::get('/assignments/{assignment}/questions', 'AssignmentSyncQuestionController@index');
    Route::get('/assignments/{assignment}/questions/view', 'AssignmentSyncQuestionController@getQuestionsToView');
    Route::post('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@store');
    Route::delete('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@destroy');


    Route::get('/enrollments', 'EnrollmentController@index');
    Route::post('/enrollments', 'EnrollmentController@store');

    Route::post('/submissions', 'SubmissionController@store');
});

Route::post('results', 'ResultController@store');
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
