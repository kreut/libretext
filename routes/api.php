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
/*
"oidc_initiation_url":"https://dev.adapt.libretexts.org/api/lti/oidc-initiation-url",
   "target_link_uri":"https://dev.adapt.libretexts.org/api/lti/target-link-uri",

Route:*/
//http://www.imsglobal.org/spec/security/v1p0/#step-1-third-party-initiated-login
//Must support both get and post according to the docs
Route::post('/lti/oidc-initiation-url', 'LTIController@initiateLoginRequest');
Route::get('/lti/oidc-initiation-url', 'LTIController@initiateLoginRequest');


Route::post('/lti/game', 'GameController@game');
Route::get('/lti/game', 'GameController@game');

Route::post('/lti/login', 'GameController@login');
Route::get('/lti/login', 'GameController@login');

Route::post('/lti/configure/{launchId}', 'GameController@configure');
Route::get('/lti/configure/{launchId}', 'GameController@configure');


Route::get('/lti/redirect-uri', 'LTIController@authenticationResponse');
Route::post('/lti/redirect-uri', 'LTIController@authenticationResponse');

Route::get('/lti/target-link-uri', 'LTIController@finalTarget');
Route::post('/lti/target-link-uri', 'LTIController@finalTarget');


Route::post('mind-touch-events/update', 'MindTouchEventController@update');
Route::post('jwt/process-answer-jwt', 'JWTController@processAnswerJWT');
Route::post('/email/send', 'EmailController@send');

Route::get('jwt/init', 'JWTController@init');
Route::get('jwt/secret', 'JWTController@signWithNewSecret');


Route::group(['middleware' => 'auth:api'], function () {
    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', 'Auth\UserController@current');

    Route::get('/get-locally-saved-query-page-contents/{pageId}', 'QueryController@getLocallySavedQueryPageContents');

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');

    Route::patch('/course-access-codes', 'CourseAccessCodeController@update');

    Route::patch('/final-grades/letter-grades/{course}', 'FinalGradeController@update');
    Route::get('/final-grades/letter-grades/default', 'FinalGradeController@getDefaultLetterGrades');
    Route::get('/final-grades/letter-grades/{course}', 'FinalGradeController@getCourseLetterGrades');
    Route::patch('/final-grades/{course}/round-scores/{roundScores}', 'FinalGradeController@roundScores');
    Route::patch('/final-grades/{course}/release-letter-grades/{letterGradesReleased}', 'FinalGradeController@releaseLetterGrades');

    Route::post('/extra-credit', 'ExtraCreditController@store');
    Route::get('/extra-credit/{course}/{user}', 'ExtraCreditController@show');

    Route::get('/courses', 'CourseController@index');
    Route::get('/courses/{course}', 'CourseController@show');

    Route::post('/courses', 'CourseController@store');
    Route::patch('/courses/{course}/students-can-view-weighted-average', 'CourseController@updateStudentsCanViewWeightedAverage');
    Route::patch('/courses/{course}', 'CourseController@update');
    Route::delete('/courses/{course}', 'CourseController@destroy');

    Route::post('/breadcrumbs', 'BreadcrumbController@index');



    Route::get('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@index');
    Route::patch('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@update');


    Route::get('assignmentGroups/{course}', 'AssignmentGroupController@getAssignmentGroupsByCourse');
    Route::post('assignmentGroups/{course}', 'AssignmentGroupController@store');

    Route::get('/assignments/courses/{course}', 'AssignmentController@index');
    Route::get('/assignments/{assignment}/get-questions-info', 'AssignmentController@getQuestionsInfo');
    Route::get('/assignments/{assignment}/summary', 'AssignmentController@getAssignmentSummary');
    Route::get('/assignments/{assignment}/scores-info', 'AssignmentController@scoresInfo');
    Route::get('/assignments/{assignment}/view-questions-info', 'AssignmentController@viewQuestionsInfo');
    Route::get('/assignments/{assignment}/get-name', 'AssignmentController@getAssignmentNameAndLatePolicy');


    Route::post('/finish-sso-registration', 'Auth\SSOController@finishRegistration');

    Route::post('/assignments', 'AssignmentController@store');
    Route::patch('/assignments/{assignment}/show-assignment-statistics/{showAssignmentStatistics}', 'AssignmentController@showAssignmentStatistics');
    Route::patch('/assignments/{assignment}/show-scores/{showScores}', 'AssignmentController@showScores');
    Route::patch('/assignments/{assignment}/show-points-per-question/{showPointsPerQuestion}', 'AssignmentController@showPointsPerQuestion');
    Route::patch('/assignments/{assignment}/solutions-released/{solutionsReleased}', 'AssignmentController@solutionsReleased');
    Route::patch('/assignments/{assignment}/show-assignment/{shown}', 'AssignmentController@showAssignment');


    Route::patch('/assignments/{assignment}', 'AssignmentController@update');
    Route::delete('/assignments/{assignment}', 'AssignmentController@destroy');


    Route::get('/scores/{course}', 'ScoreController@index');
    Route::get('/scores/{course}/get-scores-by-user', 'ScoreController@getScoresByUser');
    Route::patch('/scores/{assignment}/{user}', 'ScoreController@update');//just doing a patch here because "no score" is consider a score
    Route::get('/scores/summary/{assignment}/{question}', 'ScoreController@getScoresByAssignmentAndQuestion');
    Route::get('/scores/{assignment}/{user}', 'ScoreController@getScoreByAssignmentAndStudent');
    Route::get('/scores/assignment/{assignment}/get-assignment-questions-scores-by-user', 'ScoreController@getAssignmentQuestionScoresByUser');


    Route::get('/extensions/{assignment}/{user}', 'ExtensionController@show');
    Route::post('/extensions/{assignment}/{user}', 'ExtensionController@store');


    Route::get('/cutups/{assignment}', 'CutupController@show');
    Route::post('/cutups/{assignment}/{question}/set-as-solution-or-submission', 'CutupController@setAsSolutionOrSubmission');

    Route::get('/tags', 'TagController@index');

    Route::post('/questions/getQuestionsByTags', 'QuestionController@getQuestionsByTags');
    Route::get('/questions/{question}', 'QuestionController@show');


    Route::get('/libreverse/library/{library}/page/{pageId}/student-learning-objectives', 'LibreverseController@getStudentLearningObjectiveByLibraryAndPageId');
    Route::get('/libreverse/library/{library}/page/{pageId}/title', 'LibreverseController@getTitleByLibraryAndPageId');


    Route::get('/learning-trees', 'LearningTreeController@index');
    Route::get('/learning-trees/{learningTree}', 'LearningTreeController@show');

    Route::post('/learning-trees/learning-tree-exists', 'LearningTreeController@learningTreeExists');
    Route::delete('/learning-trees/{learningTree}', 'LearningTreeController@destroy');
    Route::patch('/learning-trees/{learningTree}', 'LearningTreeController@update');
    Route::post('/learning-trees/info', 'LearningTreeController@storeLearningTreeInfo');
    Route::post('/learning-trees/info/{learningTree}', 'LearningTreeController@updateLearningTreeInfo');

    Route::post('/logs', 'LogController@store');

    Route::get('/learning-trees/validate-remediation/{library}/{pageId}', 'LearningTreeController@validateLearningTreeNode');

    Route::get('/assignments/{assignment}/{question}/last-submitted-info', 'AssignmentSyncQuestionController@updateLastSubmittedAndLastResponse');
    Route::get('/assignments/{assignment}/questions/ids', 'AssignmentSyncQuestionController@getQuestionIdsByAssignment');
    Route::get('/assignments/{assignment}/questions/question-info', 'AssignmentSyncQuestionController@getQuestionInfoByAssignment');
    Route::get('/assignments/{assignment}/questions/view', 'AssignmentSyncQuestionController@getQuestionsToView');
    Route::get('/assignments/{assignment}/questions/summary', 'AssignmentSyncQuestionController@getQuestionSummaryByAssignment');

    Route::post('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@store');
    Route::post('/assignments/{assignment}/learning-trees/{learningTree}', 'AssignmentQuestionSyncLearningTreeController@store');

    Route::patch('/assignments/{assignment}/questions/{question}/toggle-question-files', 'AssignmentSyncQuestionController@toggleQuestionFiles');
    Route::patch('/assignments/{assignment}/questions/{question}/update-points', 'AssignmentSyncQuestionController@updatePoints');
    Route::delete('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@destroy');


    Route::get('/enrollments', 'EnrollmentController@index');
    Route::post('/enrollments', 'EnrollmentController@store');

    Route::patch('/submissions/{assignment}/{question}/explored-learning-tree', 'SubmissionController@exploredLearningTree');
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
