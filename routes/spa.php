<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SPA Routes
|--------------------------------------------------------------------------
|
| Here is where you can register SPA routes for your frontend. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "spa" middleware group.
|
*/
Route::get('/question-media-player/{media}/{start_time?}', 'QuestionMediaController@index');
Route::get('/discussion-comments/media-player/{key}/{key_id}/is-phone/{is_phone?}', 'DiscussionCommentController@mediaPlayer');
Route::get('/submission-audio/media-player/assignment/{assignment_id}/s3_key/{s3_key}/is-phone/{is_phone?}', 'SubmissionAudioController@MediaPlayer');
Route::get('/apple-app-site-association', 'MobileAppController@appleAppSiteAssociation');
Route::get('/conductor-media/{src}', 'QuestionMediaController@conductorMedia');
Route::get('/.well-known/assetlinks.json', 'MobileAppController@androidAssetLink');
Route::get('{path}', 'SpaController')->where('path', '(.*)');
