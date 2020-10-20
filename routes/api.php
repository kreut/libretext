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
\JWTAuth::getJWTProvider()->setSecret('IJILAu/ZIU3qUCKg7VA1DCAacTYnENHp+3iNwYDTpBeooxiLmhTz+KlDYE+poCaiAO7pnMNgRcjya5bJUnVD9DctB8e38xqDX2p8hTMrh+Fog6ibMnYrw4JHYVistxRjpx2TKCCeBFRyIIQXjwvlOOLjKvy+np7j0JV8eXXIXUhSH2wbjfjKofWE9Ugv9d8XhRhIX9VUTe0LDPSf3uBmkK20wm/RB5DErYMCfP/SH5362QWHfIrJeURtDwL2a4x4TYiLlNr1kqyQYPyB+hdZJipvOjaE/P+ZuLl5l7fwVI5VjSSdIXPeBit3fvVkXrXLkVvVVgEAHTOUZvVVh6q/yioq+e2qn8xqoFJVBPifJMHTU0dWmo2IiPJKqFufgl/0eJQh2KEE6B2qtGVF/vcZ26J3g08f/JIHvur/aIv/Mqv7BxSTY8T3ErpfCsToZfPM2p5b4Ii7cjweMbsJUKqVS213agXA8xYDPFaJYZElK78kJhGkVFYXVhC2YEs8GwmPGpAOb3nDCFSfknBohSHsba2GV1BONg1h05jkWKll8QOfbMTCLBaTj87oDaTq7ADZBjalMfwR3fmy9qETv8g0yesUFawde6XsoIH3NATYRaluM5WR7cgFwmx047QGH93/jKOY/WE0SkmAq+63w4yQgY6uMiAEb2D6RQeOIy4RU2eNll7xixc9w40ZwiLCazoPbGVRAtDA5RiNnodTNybGLhl+hQqAX23DUUoJ0C7Cho3dAvraikT2tFa9UKPF5J1E3n1a24jgQO+haXWSotrg99+FXimlh0RGkXHwQb4UZDlcTsWTA5zhxYuJnE3ZAeGS5VaKBw8QbP5ErzjDThM61oR9ECfQdBLwNGK8yiZNfHDqTo1IM1+QOKxkugw4agcQnoYqphOG733ekMbMu9ojCj2NafFR6nUxKAd36Tf0+plSfu4cIvfHjJYHqTBZdU5w9+7rySfsi4PEyfNbGK8xqxSGDRphNRkZUu25o+BwNHHBSwMDI32wVvXW3brTM2jddglsoXD6XxVVSYFs0QAUJeAPjCPWgCh0JzHnvwoD5sefiUaZFfQHvEBO12JT6C8H4WCEI3aEU4lO2iXWWSP/qospQ8NZI8/NpYTQOz2D5tHFPhAwm9WLMoryE47E9cZk3BEzxbv6DB96bk6IJlBZwZJEMH/gOQw2sMDqjXrlolTKvGWAqJD0LjyEmFR6SomMbtl8QTlkuK7yujPnUqC61vyLgx1jIyPZ7ZxoNZbWmoK56ns1zF9s+ibxpJaDzM94m0vZmpS7og9YbDJRuwRND7Vgv8pVbvqqGWXn793UVPMjc/+ez+GIfg==');

Route::post('mind-touch-events/update', 'MindTouchEventController@update');
Route::post('jwt/process-answer-jwt', 'JWTController@processAnswerJWT');
Route::post('/contact-us', 'ContactUsController@contactUs');
Route::get('jwt/init', 'JWTController@init');


Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', 'Auth\UserController@current');

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');

    Route::patch('/course-access-codes', 'CourseAccessCodeController@update');

    Route::get('/courses', 'CourseController@index');
    Route::post('/courses', 'CourseController@store');
    Route::patch('/courses/{course}', 'CourseController@update');
    Route::delete('/courses/{course}', 'CourseController@destroy');

    Route::get('assignments/courses/{course}/', 'AssignmentController@index');
    Route::get('/assignments/{assignment}', 'AssignmentController@show');
    Route::post('/assignments', 'AssignmentController@store');
    Route::patch('/assignments/{assignment}/release-solutions', 'AssignmentController@releaseSolutions');
    Route::patch('/assignments/{assignment}', 'AssignmentController@update');
    Route::delete('/assignments/{assignment}', 'AssignmentController@destroy');


    Route::get('/scores/{course}', 'ScoreController@index');
    Route::patch('/scores/{assignment}/{user}', 'ScoreController@update');//just doing a patch here because "no score" is consider a score
    Route::get('/scores/{assignment}/{user}', 'ScoreController@getScoreByAssignmentAndStudent');

    Route::get('/extensions/{assignment}/{user}', 'ExtensionController@show');
    Route::post('/extensions/{assignment}/{user}', 'ExtensionController@store');


    Route::get('/cutups/{assignment}', 'CutupController@show');
    Route::post('/cutups/{assignment}/{question}/{cutup}/set-as-solution-or-submission', 'CutupController@setAsSolutionOrSubmission');

    Route::get('/tags', 'TagController@index');

    Route::post('/questions/getQuestionsByTags', 'QuestionController@getQuestionsByTags');
    Route::get('/questions/{question}', 'QuestionController@show');


    Route::get('/libreverse/library/{library}/page/{pageId}/student-learning-objectives', 'LibreverseController@getStudentLearningObjectiveByLibraryAndPageId');
    Route::get('/libreverse/library/{library}/page/{pageId}/title', 'LibreverseController@getTitleByLibraryAndPageId');


    Route::get('/learning-trees/{question}','LearningTreeController@show');
    Route::post('/learning-trees','LearningTreeController@store');
    Route::get('/learning-trees/validate-remediation/{library}/{pageId}','LearningTreeController@validateRemediation');

    Route::get('/assignments/{assignment}/{question}/last-submitted-info', 'AssignmentSyncQuestionController@updateLastSubmittedAndLastResponse');
    Route::get('/assignments/{assignment}/questions/ids', 'AssignmentSyncQuestionController@getQuestionIdsByAssignment');
    Route::get('/assignments/{assignment}/questions/question-info', 'AssignmentSyncQuestionController@getQuestionInfoByAssignment');
    Route::get('/assignments/{assignment}/questions/view', 'AssignmentSyncQuestionController@getQuestionsToView');
    Route::post('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@store');
    Route::patch('/assignments/{assignment}/questions/{question}/toggle-question-files', 'AssignmentSyncQuestionController@toggleQuestionFiles');
    Route::patch('/assignments/{assignment}/questions/{question}/update-points', 'AssignmentSyncQuestionController@updatePoints');
    Route::delete('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@destroy');


    Route::get('/enrollments', 'EnrollmentController@index');
    Route::post('/enrollments', 'EnrollmentController@store');

    Route::post('/submissions', 'SubmissionController@store');

    Route::get('/assignment-files/assignment-file-info-by-student/{assignment}', 'AssignmentFileController@getAssignmentFileInfoByStudent');
    Route::get('/submission-files/{type}/{assignment}/{gradeView}', 'SubmissionFileController@getSubmissionFilesByAssignment')->where('type', '(question|assignment)');

    Route::put('/solution-files', 'SolutionController@storeSolutionFile');
    Route::post('/solution-files/download', 'SolutionController@downloadSolutionFile');

    Route::put('/submission-files/file-feedback', 'SubmissionFileController@storeFileFeedback');
    Route::post('/submission-files/text-feedback', 'SubmissionFileController@storeTextFeedback');
    Route::post('/submission-files/score', 'SubmissionFileController@storeScore');
    Route::put('/submission-files', 'SubmissionFileController@storeSubmissionFile');
    Route::post('/submission-files/get-temporary-url-from-request', 'SubmissionFileController@getTemporaryUrlFromRequest');
    Route::post('/submission-files/download', 'SubmissionFileController@downloadSubmissionFile');


    Route::post('/invitations/{course}', 'InvitationController@emailInvitation');

    Route::get('/grader/{course}', 'GraderController@getGradersByCourse');
    Route::delete('/grader/{course}/{user}', 'GraderController@removeGraderFromCourse');



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
