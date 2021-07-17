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

Route::get('/kubernetes', 'KubernetesController@metrics');
Route::get('/lti/user', 'LTIController@getUser');
Route::post('lti/link-assignment-to-lms/{assignment}', 'LTIController@linkAssignmentToLMS');
Route::post('/lti/oidc-initiation-url-2', 'LTIController@initiateLoginRequest');
Route::get('/lti/oidc-initiation-url-2', 'LTIController@initiateLoginRequest');

Route::post('/lti/configure-2/{launchId}', 'LTIController@configure');
Route::get('/lti/configure-2/{launchId}', 'LTIController@configure');


Route::get('/lti/redirect-uri-2', 'LTIController@authenticationResponse');
Route::post('/lti/redirect-uri-2', 'LTIController@authenticationResponse');

Route::post('/lti/game', 'LTIController@authenticationResponse');
Route::get('/lti/game', 'LTIController@authenticationResponse');

Route::post('/lti/login', 'GameController@login');
Route::get('/lti/login', 'GameController@login');

Route::post('/lti/configure/{launchId}', 'LTIController@configure');
Route::get('/lti/configure/{launchId}', 'LTIController@configure');


Route::get('/lti/redirect-uri', 'LTIController@authenticationResponse');
Route::post('/lti/redirect-uri', 'LTIController@authenticationResponse');

Route::get('/lti/target-link-uri', 'LTIController@finalTarget');
Route::post('/lti/target-link-uri', 'LTIController@finalTarget');


Route::post('mind-touch-events/update', 'MindTouchEventController@update');
Route::post('jwt/process-answer-jwt', 'JWTController@processAnswerJWT');
Route::post('/email/send', 'EmailController@send');

Route::get('jwt/init', 'JWTController@init');
Route::get('jwt/secret', 'JWTController@signWithNewSecret');


Route::group(['middleware' => ['auth:api', 'throttle:240,1']], function () {

    Route::patch('/cookie/set-question-view/{questionView}', 'CookieController@setQuestionView');
    Route::patch('/cookie/set-assignment-group-filter/{course}/{chosenAssignmentGroup}', 'CookieController@setAssignmentGroupFilter');

    Route::post('logout', 'Auth\LoginController@logout');

    Route::get('/user', 'Auth\UserController@current');
    Route::get('/user/all', 'Auth\UserController@getAll');
    Route::post('/user/toggle-student-view', 'Auth\UserController@toggleStudentView');
    Route::post('/user/login-as', 'Auth\UserController@loginAs');
    Route::post('/user/login-as-student-in-course', 'Auth\UserController@loginAsStudentInCourse');
    Route::get('/user/get-session', 'Auth\UserController@getSession');
    Route::post('/user/instructors-with-public-courses', 'UserController@getInstructorsWithPublicCourses');

    Route::get('/get-locally-saved-page-contents/{library}/{pageId}', 'LibretextController@getLocallySavedPageContents');

    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');


    Route::patch('notifications/assignments', 'NotificationController@update');
    Route::get('notifications/assignments', 'NotificationController@show');

    Route::patch('/final-grades/letter-grades/{course}', 'FinalGradeController@update');
    Route::get('/final-grades/letter-grades/default', 'FinalGradeController@getDefaultLetterGrades');
    Route::get('/final-grades/letter-grades/{course}', 'FinalGradeController@getCourseLetterGrades');
    Route::patch('/final-grades/{course}/round-scores/{roundScores}', 'FinalGradeController@roundScores');
    Route::patch('/final-grades/{course}/release-letter-grades/{letterGradesReleased}', 'FinalGradeController@releaseLetterGrades');

    Route::post('/extra-credit', 'ExtraCreditController@store');
    Route::get('/extra-credit/{course}/{user}', 'ExtraCreditController@show');

    Route::get('/assign-to-groups/{course}', 'AssignToGroupController@assignToGroups');

    Route::get('/schools', 'SchoolController@index');
    Route::get('/schools/public-courses', 'SchoolController@getSchoolsWithPublicCourses');


    Route::get('/courses', 'CourseController@index');
    Route::get('/courses/is-alpha/{course}', 'CourseController@isAlpha');
    Route::get('/courses/last-school', 'CourseController@getLastSchool');
    Route::get('/courses/assignments', 'CourseController@getCoursesAndAssignments');
    Route::get('/courses/public/{instructor?}', 'CourseController@getPublicCourses');
    Route::get('/courses/importable', 'CourseController@getImportable');
    Route::post('/courses/import/{course}', 'CourseController@import');

    Route::get('/courses/{course}', 'CourseController@show');
    Route::patch('/courses/{course}/show-course/{shown}', 'CourseController@showCourse');

    Route::post('/courses', 'CourseController@store');
    Route::patch('/courses/{course}/students-can-view-weighted-average', 'CourseController@updateStudentsCanViewWeightedAverage');
    Route::patch('/courses/{course}/show-z-scores', 'CourseController@updateShowZScores');

    Route::patch('/courses/{course}', 'CourseController@update');
    Route::delete('/courses/{course}', 'CourseController@destroy');

    Route::patch('/assignments/{course}/order', 'AssignmentsController@order');

    Route::post('/breadcrumbs', 'BreadcrumbController@index');


    Route::get('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@index');
    Route::patch('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@update');


    Route::get('assignmentGroups/{course}', 'AssignmentGroupController@getAssignmentGroupsByCourse');
    Route::post('assignmentGroups/{course}', 'AssignmentGroupController@store');
    Route::get('assignmentGroups/get-assignment-group-filter/{course}', 'AssignmentGroupController@getAssignmentGroupFilter');

    Route::get('/assignments/{course}/assignments-and-users', 'AssignmentController@getAssignmentsAndUsers');
    Route::patch('/assignments/{course}/order', 'AssignmentController@order');
    Route::get('/assignments/importable-by-user/{course}', 'AssignmentController@getImportableAssignmentsByUser');
    Route::post('/assignments/import/{course}', 'AssignmentController@importAssignment');
    Route::get('/assignments/courses/{course}', 'AssignmentController@index');
    Route::get('/assignments/courses/public/{course}/names', 'AssignmentController@getAssignmentNamesForPublicCourse');
    Route::get('/assignments/{assignment}/get-questions-info', 'AssignmentController@getQuestionsInfo');
    Route::get('/assignments/{assignment}/summary', 'AssignmentController@getAssignmentSummary');
    Route::get('/assignments/{assignment}/scores-info', 'AssignmentController@scoresInfo');
    Route::get('/assignments/{assignment}/view-questions-info', 'AssignmentController@viewQuestionsInfo');
    Route::get('/assignments/{assignment}/get-info-for-grading', 'AssignmentController@getInfoForGrading');
    Route::post('/assignments/{assignment}/validate-assessment-type', 'AssignmentController@validateAssessmentType');

    Route::post('/sso/finish-registration', 'Auth\SSOController@finishRegistration');
    Route::get('/sso/completed-registration', 'Auth\SSOController@completedRegistration');
    Route::get('/sso/is-sso-user', 'Auth\SSOController@isSSOUser');

    Route::post('/assignments', 'AssignmentController@store');
    Route::post('/assignments/{assignment}/create-assignment-from-template', 'AssignmentController@createAssignmentFromTemplate');
    Route::patch('/assignments/{assignment}/show-assignment-statistics/{showAssignmentStatistics}', 'AssignmentController@showAssignmentStatistics');
    Route::patch('/assignments/{assignment}/show-scores/{showScores}', 'AssignmentController@showScores');
    Route::patch('/assignments/{assignment}/show-points-per-question/{showPointsPerQuestion}', 'AssignmentController@showPointsPerQuestion');
    Route::patch('/assignments/{assignment}/solutions-released/{solutionsReleased}', 'AssignmentController@solutionsReleased');
    Route::patch('/assignments/{assignment}/show-assignment/{shown}', 'AssignmentController@showAssignment');


    Route::patch('/assignments/{assignment}', 'AssignmentController@update');
    Route::delete('/assignments/{assignment}', 'AssignmentController@destroy');

    Route::post('/s3/pre-signed-url', 'S3Controller@preSignedURL');

    Route::get('/auto-graded-and-file-submissions/{assignment}/{question}/get-auto-graded-and-file-submissions-by-assignment-and-question-and-student','AutoGradedAndFileSubmissionController@getAutoGradedAndFileSubmissionsByAsssignmentAndQuestionAndStudent');
    Route::get('/scores/{assignment}/{question}/get-scores-by-assignment-and-question', 'ScoreController@getScoresByAssignmentAndQuestion');
    Route::put('/scores/{assignment}/upload-override-scores', 'ScoreController@uploadOverrideScores');
    Route::post('/scores/over-total-points/{assignment}/{question}', 'ScoreController@overTotalPoints');
    Route::patch('/scores/{assignment}/override-scores', 'ScoreController@overrideScores');
    Route::get('/scores/{course}/get-course-scores-by-user', 'ScoreController@getCourseScoresByUser');
    Route::get('/scores/{course}/{sectionId}', 'ScoreController@index');
    Route::get('/scores/assignment-user/{assignment}/{user}', 'ScoreController@getScoreByAssignmentAndStudent');

    Route::patch('/scores/{assignment}/{user}', 'ScoreController@update');//just doing a patch here because "no score" is consider a score
    Route::get('/scores/summary/{assignment}/{question}', 'ScoreController@getScoresByAssignmentAndQuestion');

    Route::get('/scores/assignment/{assignment}/get-assignment-questions-scores-by-user', 'ScoreController@getAssignmentQuestionScoresByUser');


    Route::get('/extensions/{assignment}/{user}', 'ExtensionController@show');
    Route::post('/extensions/{assignment}/{user}', 'ExtensionController@store');


    Route::get('/cutups/{assignment}', 'CutupController@show');
    Route::patch('/cutups/{assignment}/{question}/solution', 'CutupController@updateSolution');

    Route::get('/tags', 'TagController@index');

    Route::post('/questions/getQuestionsByTags', 'QuestionController@getQuestionsByTags');
    Route::post('/questions/default-import-library', 'QuestionController@storeDefaultImportLibrary');
    Route::get('/questions/default-import-library', 'QuestionController@getDefaultImportLibrary');
    Route::post('/questions/{assignment}/direct-import-questions', 'QuestionController@directImportQuestions');



     Route::get('/beta-assignments/get-from-alpha-assignment/{alpha_assignment}', 'BetaAssignmentController@getFromAlphaAssignment');
    Route::get('/questions/{question}', 'QuestionController@show');
    Route::get('/questions/properties/{question}', 'QuestionController@getProperties');
    Route::patch('/questions/properties/{question}', 'QuestionController@updateProperties');

    Route::get('/libreverse/library/{library}/page/{pageId}/student-learning-objectives', 'LibreverseController@getStudentLearningObjectiveByLibraryAndPageId');
    Route::get('/libreverse/library/{library}/page/{pageId}/title', 'LibreverseController@getTitleByLibraryAndPageId');
    Route::post('/libreverse/library/titles', 'LibreverseController@getTitles');

    Route::get('/learning-trees', 'LearningTreeController@index');
    Route::get('/learning-trees/{learningTree}', 'LearningTreeController@show');
    Route::post('/learning-trees/import', 'LearningTreeController@import');
    Route::post('/learning-trees/{learningTree}/create-learning-tree-from-template', 'LearningTreeController@createLearningTreeFromTemplate');


    Route::patch('/learning-tree-histories/{learningTree}', 'LearningTreeHistoryController@updateLearningTreeFromHistory');



    Route::post('/learning-trees/learning-tree-exists', 'LearningTreeController@learningTreeExists');
    Route::delete('/learning-trees/{learningTree}', 'LearningTreeController@destroy');
    Route::patch('/learning-trees/nodes/{learningTree}', 'LearningTreeController@updateNode');
    Route::patch('/learning-trees/{learningTree}', 'LearningTreeController@updateLearningTree');
    Route::post('/learning-trees/info', 'LearningTreeController@storeLearningTreeInfo');
    Route::post('/learning-trees/info/{learningTree}', 'LearningTreeController@updateLearningTreeInfo');

    Route::post('/store', 'DataShopController@store');

    Route::get('/learning-trees/validate-remediation/{library}/{pageId}', 'LearningTreeController@validateLearningTreeNode');

    Route::get('/sections/can-create-student-access-codes', 'SectionController@canCreateStudentAccessCodes');
    Route::get('/sections/{course}', 'SectionController@index');
    Route::get('/sections/real-enrolled-users/{section}', 'SectionController@realEnrolledUsers');
    Route::post('/sections/{course}', 'SectionController@store');
    Route::patch('/sections/{section}', 'SectionController@update');
    Route::delete('/sections/{section}', 'SectionController@destroy');
    Route::patch('/sections/refresh-access-code/{section}', 'SectionController@refreshAccessCode');


    Route::get('/assignments/{assignment}/{question}/last-submitted-info', 'AssignmentSyncQuestionController@updateLastSubmittedAndLastResponse');
    Route::get('/assignments/{assignment}/questions/ids', 'AssignmentSyncQuestionController@getQuestionIdsByAssignment');
    Route::get('/assignments/{assignment}/questions/question-info', 'AssignmentSyncQuestionController@getQuestionInfoByAssignment');
    Route::get('/assignments/{assignment}/questions/view', 'AssignmentSyncQuestionController@getQuestionsToView');
    Route::get('/assignments/{assignment}/questions/titles', 'AssignmentSyncQuestionController@getQuestionTitles');
    Route::get('/assignments/{assignment}/questions/summary', 'AssignmentSyncQuestionController@getQuestionSummaryByAssignment');
    Route::patch('/assignments/{assignment}/remix-assignment-with-chosen-questions', 'AssignmentSyncQuestionController@remixAssignmentWithChosenQuestions');

    Route::get('/assignments/{assignment}/validate-can-switch-to-compiled-pdf', 'AssignmentSyncQuestionController@validateCanSwitchToCompiledPdf');
    Route::get('/assignments/{assignment}/validate-can-switch-to-or-from-compiled-pdf', 'AssignmentSyncQuestionController@validateCanSwitchToOrFromCompiledPdf');

    Route::post('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@store');
    Route::post('/assignments/{assignment}/learning-trees/{learningTree}', 'AssignmentQuestionSyncLearningTreeController@store');

    Route::get('/assignments/{assignment}/questions/{question}/get-clicker-status', 'AssignmentSyncQuestionController@getClickerStatus');
    Route::post('/assignments/{assignment}/questions/{question}/start-clicker-assessment', 'AssignmentSyncQuestionController@startClickerAssessment');

    Route::patch('/assignments/{assignment}/questions/{question}/open-ended-default-text', 'AssignmentSyncQuestionController@storeOpenEndedDefaultText');


    Route::get('/assignments/{assignment}/clicker-question', 'AssignmentSyncQuestionController@getClickerQuestion');


    Route::patch('/assignments/{assignment}/questions/{question}/update-open-ended-submission-type', 'AssignmentSyncQuestionController@updateOpenEndedSubmissionType');
    Route::patch('/assignments/{assignment}/questions/{question}/update-points', 'AssignmentSyncQuestionController@updatePoints');
    Route::patch('/assignments/{assignment}/questions/order', 'AssignmentSyncQuestionController@order');


    Route::delete('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@destroy');


    Route::post('/submission-audios/audio-feedback/{user}/{assignment}/{question}', 'SubmissionAudioController@storeAudioFeedback');
    Route::post('/submission-audios/{assignment}/{question}', 'SubmissionAudioController@store');
    Route::post('/submission-audios/error', 'SubmissionAudioController@logError');


    Route::get('/assignment-grader-access/{assignment}', 'AssignmentGraderAccessController@index');
    Route::patch('assignment-grader-access/{assignment}/{grader}/{access_level}', 'AssignmentGraderAccessController@updateGrader');
    Route::patch('assignment-grader-access/{assignment}/{access_level}', 'AssignmentGraderAccessController@updateAllGraders');


    Route::get('/enrollments', 'EnrollmentController@index');
    Route::get('/enrollments/{course}/details', 'EnrollmentController@details');
    Route::post('/enrollments', 'EnrollmentController@store');
    Route::delete('/enrollments/{section}/{user}', 'EnrollmentController@destroy');
    Route::patch('/enrollments/{course}/{user}', 'EnrollmentController@update');

    Route::patch('/submissions/{assignment}/{question}/scores', 'SubmissionController@updateScores');
    Route::patch('/submissions/{assignment}/{question}/explored-learning-tree', 'SubmissionController@exploredLearningTree');
    Route::post('/submissions', 'SubmissionController@store');
    Route::get('/submissions/{assignment}/questions/{question}/pie-chart-data', 'SubmissionController@submissionPieChartData');


    Route::get('/canned-responses', 'CannedResponseController@index');
    Route::post('/canned-responses', 'CannedResponseController@store');
    Route::delete('/canned-responses/{cannedResponse}', 'CannedResponseController@destroy');

    Route::get('/submission-files/ungraded-submissions/{course}', 'SubmissionFileController@getUngradedSubmissions');
    Route::patch('/submission-files/{assignment}/{question}/page', 'SubmissionFileController@updatePage');
    Route::patch('/submission-files/{assignment}/{question}/scores', 'SubmissionFileController@updateScores');
    Route::get('/assignment-files/assignment-file-info-by-student/{assignment}', 'AssignmentFileController@getAssignmentFileInfoByStudent');
    Route::get('/submission-files/{assignment}/{question}/{sectionId}/{gradeView}', 'SubmissionFileController@getSubmissionFilesByAssignment');
    Route::post('/submission-files/get-files-from-s3/{assignment}/{question}/{studentUser}', 'SubmissionFileController@getFilesFromS3');
    Route::post('/submission-files/can-submit-file-submission', 'SubmissionFileController@canSubmitFileSubmission');


    Route::post('/solutions/text/{assignment}/{question}', 'SolutionController@storeText');
    Route::post('/solution-files/audio/{assignment}/{question}', 'SolutionController@storeAudioSolutionFile');
    Route::put('/solution-files', 'SolutionController@storeSolutionFile');
    Route::post('/solution-files/download', 'SolutionController@downloadSolutionFile');
    Route::delete('/solution-files/{assignment}/{question}', 'SolutionController@destroy');

    Route::post('/submission-texts', 'SubmissionTextController@store');
    Route::delete('/submission-texts/{assignment}/{question}', 'SubmissionTextController@destroy');

    Route::put('/submission-files/file-feedback', 'SubmissionFileController@storeFileFeedback');
    Route::post('/submission-files/text-feedback', 'SubmissionFileController@storeTextFeedback');


    Route::post('/submission-files/score', 'SubmissionFileController@storeScore');
    Route::put('/submission-files', 'SubmissionFileController@storeSubmissionFile');
    Route::post('/submission-files/get-temporary-url-from-request', 'SubmissionFileController@getTemporaryUrlFromRequest');
    Route::post('/submission-files/download', 'SubmissionFileController@downloadSubmissionFile');


    Route::post('/invitations/grader', 'InvitationController@emailGraderInvitation');

    Route::post('/graders/', 'GraderController@store');
    Route::get('/graders/{course}', 'GraderController@getGradersByCourse');
    Route::patch('/graders/{user}', 'GraderController@update');
    Route::delete('/graders/{course}/{user}', 'GraderController@removeGraderFromCourse');

    Route::patch('/head-graders/{course}/{user}', 'HeadGraderController@update');
    Route::delete('/head-graders/{course}', 'HeadGraderController@destroy');


    Route::get('/grader-notifications/{course}', 'GraderNotificationController@index');
    Route::patch('/grader-notifications/{course}', 'GraderNotificationController@update');

});

Route::group(['middleware' => ['guest:api', 'throttle:30,1']], function () {

    Route::post('login', 'Auth\LoginController@login');
    Route::post('register', 'Auth\RegisterController@register');

    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend');

    Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');
});
