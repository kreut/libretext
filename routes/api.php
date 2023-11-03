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

Route::get('test', function () {
    event(new App\Events\ClickerStatus('ooga'));
    return "Event has been sent!";
});


Route::get('/lms/create-course', 'LmsController@createCourse');
Route::post('/open-ai/results/{type}', 'OpenAIController@results');

Route::get('/kubernetes', 'KubernetesController@metrics');
Route::post('/lti/get-token-by-lti-token-id', 'LTIController@getTokenByLtiTokenId');
Route::get('/lti/user', 'LTIController@getUser');
Route::post('/refresh-token', 'LTIController@refreshToken');

Route::post('lti/link-assignment-to-lms/{assignment}', 'LTIController@linkAssignmentToLMS');

Route::post('/lti/oidc-initiation-url', 'LTIController@initiateLoginRequest');
Route::get('/lti/oidc-initiation-url', 'LTIController@initiateLoginRequest');
Route::get('/lti/redirect-uri/{campus_id?}', 'LTIController@authenticationResponse');
Route::post('/lti/redirect-uri/{campus_id?}', 'LTIController@authenticationResponse');

Route::get('/lti/json-config/{campus_id}', 'LTIController@jsonConfig');
Route::get('/lti/public-jwk', 'LTIController@publicJWK');
Route::post('/lti-registration/email-details', 'LtiRegistrationController@emailDetails');
Route::get('/lti-registration/is-valid-campus-id/{type}/{campusId}', 'LtiRegistrationController@isValidCampusId');
Route::patch('/lti-registration/api-key', 'LtiRegistrationController@updateAPIKey');

Route::post('mind-touch-events/update', 'MindTouchEventController@update');
Route::post('jwt/process-answer-jwt', 'JWTController@processAnswerJWT');
Route::post('/email/send', 'EmailController@send');

Route::get('jwt/init', 'JWTController@init');
Route::get('jwt/secret', 'JWTController@signWithNewSecret');

Route::get('/beta-assignments/get-from-alpha-assignment/{alpha_assignment}', 'BetaAssignmentController@getBetaCourseFromAlphaAssignment');
Route::get('/beta-assignments/is-beta-assignment/{assignment}', 'BetaAssignmentController@isBetaAssignment');


Route::get('/h5p-collections', 'H5PCollectionController@index');
Route::post('/h5p-collections/validate-import', 'H5PCollectionController@validateImport');


Route::get('/courses/commons', 'CourseController@getCommonsCourses');
Route::get('/courses/open', 'CourseController@getOpenCourses');
Route::get('/courses/all', 'CourseController@getAllCourses');
Route::get('/assignments/open/{type}/{course}', 'AssignmentController@getOpenCourseAssignments');

Route::get('/user/login-as-formative-student/assignment/{assignment}', 'Auth\UserController@loginToAssignmentAsFormativeStudent');

Route::get('/assignments/names-ids-by-course/{course}', 'AssignmentController@getAssignmentNamesIdsByCourse');
Route::get('/analytics/nursing/{download}', 'AnalyticsController@nursing');
Route::get('/analytics/scores/course/{course}', 'AnalyticsController@scoresByCourse');
Route::get('/analytics/proportion-correct-by-assignment/course/{course}', 'AnalyticsController@proportionCorrectByAssignment');
Route::get('/analytics/learning-outcomes', 'AnalyticsController@LearningOutcomes');
Route::get('/analytics/question-learning-outcome', 'AnalyticsController@QuestionLearningOutcome');
Route::get('/analytics/enrollments/{start_date?}/{end_date?}', 'AnalyticsController@enrollments');
Route::get('/analytics/{start_date?}/{end_date?}', 'AnalyticsController@index');
Route::get('/analytics/review-history/assignment/{assignment}', 'AnalyticsController@getReviewHistoryByAssignment');


Route::get('/libre-one-access-code/user/{access_code}', 'LibreOneAccessCodeController@getUserByAccessCode');

Route::post('/analytics-dashboard/sync/{analytics_course_id}', 'AnalyticsDashboardController@sync');
Route::post('/analytics-dashboard/unsync/{analytics_course_id}', 'AnalyticsDashboardController@unsync');

Route::get('/schools', 'SchoolController@index');
Route::post('/questions/bulk-upload-template/{import_template}/{course?}', 'QuestionController@getBulkUploadTemplate');

Route::get('/time-zones', 'TimeZoneController@index');
Route::get('/users/get-cookie-user-jwt', 'UserController@getCookieUserJWT');

Route::group(['middleware' => ['auth:api', 'throttle:550,1']], function () {

    Route::get('/updated-information-first-application/{assignment}', 'UpdatedInformationFirstApplicationController@index');
    Route::patch('/updated-information-first-application', 'UpdatedInformationFirstApplicationController@update');

    Route::post('/fcm-tokens', 'FCMTokenController@store');
    Route::get('/fcm-tokens/test-send-notification', 'FCMTokenController@testSendNotification');

    Route::post('/tester/email-results/{student}', 'TesterController@emailResults');
    Route::post('/tester', 'TesterController@store');
    Route::get('/tester/{course}', 'TesterController@index');
    Route::delete('/tester/course/{course}/user/{tester}/{removeOption}', 'TesterController@destroy');

    Route::get('/lti-registration', 'LtiRegistrationController@index');
    Route::post('/lti-registration/save', 'LtiRegistrationController@store');
    Route::patch('/lti-registration/active/{ltiRegistration}', 'LtiRegistrationController@active');


    Route::post('/access-code', 'AccessCodeController@store');
    Route::post('/access-code/email', 'AccessCodeController@email');

    Route::get('/lti-school', 'LtiSchoolController@index');

    Route::post('/users/set-anonymous-user-session', 'UserController@setAnonymousUserSession');


    Route::get('/question-editor', 'QuestionEditorController@index');
    Route::delete('/question-editor/{questionEditorUser}', 'QuestionEditorController@destroy');

    Route::patch('/cookie/set-question-view/{questionView}', 'CookieController@setQuestionView');
    Route::patch('/cookie/set-assignment-group-filter/{course}/{chosenAssignmentGroup}', 'CookieController@setAssignmentGroupFilter');
    Route::patch('/cookie/set-ferpa-mode/{ferpaMode}', 'CookieController@setFerpaMode');

    Route::post('logout', 'Auth\LoginController@logout');
    Route::delete('/user', 'Auth\LoginController@destroy');
    Route::get('/user', 'Auth\UserController@current');
    Route::get('/user/all', 'Auth\UserController@getAll');

    Route::post('/user/toggle-student-view', 'Auth\UserController@toggleStudentView');
    Route::post('/user/login-as', 'Auth\UserController@loginAs');
    Route::post('/user/exit-login-as', 'Auth\UserController@exitLoginAs');
    Route::post('/user/login-as-student-in-course', 'Auth\UserController@loginAsStudentInCourse');

    Route::get('/user/get-session', 'Auth\UserController@getSession');
    Route::post('/user/instructors-with-public-courses', 'UserController@getInstructorsWithPublicCourses');
    Route::get('/user/question-editors', 'UserController@getAllQuestionEditors');
    Route::patch('/user/student-email/{student}', 'UserController@updateStudentEmail');
    Route::delete('/user/{student}/course/{course}', 'UserController@destroy');

    Route::get('/get-locally-saved-page-contents/{library}/{pageId}', 'LibretextController@getLocallySavedPageContents');
    Route::get('/get-header-html/{question}/{revision_number?}', 'LibretextController@getHeaderHtml');
    Route::post('/libretexts/solution-error', 'LibretextController@emailSolutionError');

    Route::patch('/pending-question-ownership-transfer-request', 'PendingQuestionOwnershipTransferController@update');
    Route::post('/libretexts/migrate', 'LibretextController@migrate');

    Route::get('/current-question-editor/{question}', 'CurrentQuestionEditorController@show');
    Route::patch('/current-question-editor/{question}', 'CurrentQuestionEditorController@update');
    Route::delete('/current-question-editor/{question}', 'CurrentQuestionEditorController@destroy');


    Route::get('/non-updated-question-revisions/course/{course}', 'NonUpdatedQuestionRevisionController@getNonUpdatedAssignmentQuestionsByCourse');
    Route::patch('/non-updated-question-revisions/update-to-latest/course/{course}', 'NonUpdatedQuestionRevisionController@updateToLatestQuestionRevisionsByCourse');


    Route::patch('settings/profile', 'Settings\ProfileController@update');
    Route::patch('settings/password', 'Settings\PasswordController@update');


    Route::post('/assignment-topics', 'AssignmentTopicController@store');
    Route::patch('/assignment-topics', 'AssignmentTopicController@update');
    Route::patch('/assignment-topics/move/from-assignment/{assignment}/to/topic/{assignmentTopic}', 'AssignmentTopicController@move');
    Route::post('/assignment-topics/delete/{assignmentTopic}', 'AssignmentTopicController@delete');

    Route::get('/assignment-topics/course/{course}', 'AssignmentTopicController@getAssignmentTopicsByCourse');
    Route::get('/assignment-topics/assignment/{assignment}', 'AssignmentTopicController@getAssignmentTopicsByAssignment');


    Route::get('/h5p-video-interaction/submissions/assignment/{assignment}/question/{question}', 'H5pVideoInteractionController@getSubmissions');

    Route::get('/saved-questions-folders/options/my-questions-folders', 'SavedQuestionsFoldersController@getMyQuestionsFoldersAsOptions');
    Route::get('/saved-questions-folders/cloned-questions-folder', 'SavedQuestionsFoldersController@getClonedQuestionsFolder');
    Route::get('/saved-questions-folders/{type}/{withH5P?}', 'SavedQuestionsFoldersController@getSavedQuestionsFoldersByType');

    Route::post('/saved-questions-folders', 'SavedQuestionsFoldersController@store');
    Route::patch('/saved-questions-folders', 'SavedQuestionsFoldersController@update');


    Route::get('/lms-api/access-token/course/{course}/code/{authorization_code}', 'LmsApiController@getAccessToken');
    Route::get('/lms-api/oauth-url/{course}', 'LmsApiController@getOAuthUrl');

    Route::get('/frameworks', 'FrameworkController@index');
    Route::post('/frameworks', 'FrameworkController@store');
    Route::delete('/frameworks/{framework}/{deleteProperties}', 'FrameworkController@destroy');

    Route::patch('/frameworks/{framework}', 'FrameworkController@update');
    Route::post('/frameworks/export/{framework}', 'FrameworkController@export');

    Route::post('/framework-levels/template', 'FrameworkLevelController@getTemplate');
    Route::post('/framework-levels/with-descriptors', 'FrameworkLevelController@storeWithDescriptors');

    Route::get('/framework-levels/framework/{framework}/parent-id/{parent}', 'FrameworkLevelController@getFrameworkLevelChildren');
    Route::delete('/framework-levels/{frameworkLevel}/descriptor-action/{descriptorAction}/level-to-move-to/{levelToMoveTo}', 'FrameworkLevelController@destroy');
    Route::get('framework-levels/same-parent/{frameworkLevel}', 'FrameworkLevelController@getFrameworkLevelsWithSameParent');
    Route::get('framework-levels/all-children/{frameworkLevel}', 'FrameworkLevelController@getAllChildren');

    Route::patch('/framework-levels/move-level', 'FrameworkLevelController@moveLevel');
    Route::patch('/framework-levels/change-position', 'FrameworkLevelController@changePosition');
    Route::get('/framework-item-sync-question/get-questions-by-descriptor/{frameworkDescriptor}', 'FrameworkItemSyncQuestionController@getQuestionsByDescriptor');


    Route::get('/framework-item-sync-question/question/{question}', 'FrameworkItemSyncQuestionController@getFrameworkItemsByQuestion');

    Route::put('/framework-levels/upload', 'FrameworkLevelController@upload');
    Route::get('/frameworks/{framework}/{question?}', 'FrameworkController@show');

    Route::post('/framework-levels', 'FrameworkLevelController@store');
    Route::patch('/framework-levels', 'FrameworkLevelController@update');

    Route::post('/framework-descriptors', 'FrameworkDescriptorController@store');
    Route::patch('/framework-descriptors/move', 'FrameworkDescriptorController@move');
    Route::patch('/framework-descriptors/{frameworkDescriptor}', 'FrameworkDescriptorController@update');
    Route::delete('/framework-descriptors/{frameworkDescriptor}', 'FrameworkDescriptorController@destroy');

    Route::post('/saved-questions-folders/delete/{savedQuestionsFolder}', 'SavedQuestionsFoldersController@destroy');
    Route::patch('/saved-questions-folders/move/{question}/from/{fromFolder}/to/{toFolder}', 'SavedQuestionsFoldersController@move');

    Route::post('/my-favorites', 'MyFavoriteController@store');

    Route::get('/learning-outcomes/default-subject', 'LearningOutcomeController@getDefaultSubject');
    Route::get('/learning-outcomes/{subject}', 'LearningOutcomeController@getLearningOutcomes');

    Route::delete('/my-favorites/folder/{savedQuestionsFolder}/question/{question}', 'MyFavoriteController@destroy');
    Route::get('/my-favorites/assignment/{assignment}', 'MyFavoriteController@getMyFavoriteQuestionIdsByAssignment');

    Route::patch('notifications/assignments', 'NotificationController@update');
    Route::get('notifications/assignments', 'NotificationController@show');

    Route::patch('/final-grades/letter-grades/{course}', 'FinalGradeController@update');
    Route::get('/final-grades/letter-grades/default', 'FinalGradeController@getDefaultLetterGrades');
    Route::get('/final-grades/letter-grades/{course}', 'FinalGradeController@getCourseLetterGrades');
    Route::patch('/final-grades/{course}/round-scores/{roundScores}', 'FinalGradeController@roundScores');
    Route::patch('/final-grades/{course}/release-letter-grades/{letterGradesReleased}', 'FinalGradeController@releaseLetterGrades');

    Route::post('/extra-credit', 'ExtraCreditController@store');
    Route::get('/extra-credit/{course}/{user}', 'ExtraCreditController@show');

    Route::post('/ckeditor/upload', 'CKEditorController@upload');
    Route::post('/ckeditor/upload&responseType=json', 'CKEditorController@upload');

    Route::get('/assign-to-groups/{course}', 'AssignToGroupController@assignToGroups');

    Route::get('/schools/public-courses', 'SchoolController@getSchoolsWithPublicCourses');
    Route::get('/courses', 'CourseController@index');
    Route::get('/courses/non-beta-courses-and-assignments', 'CourseController@getNonBetaCoursesAndAssignments');
    Route::patch('/courses/{course}/iframe-properties', 'CourseController@updateIFrameProperties');
    Route::get('/courses/{course}/has-h5p-questions', 'CourseController@hasH5PQuestions');
    Route::get('/courses/is-alpha/{course}', 'CourseController@isAlpha');
    Route::get('/courses/warnings/{course}', 'CourseController@getWarnings');
    Route::get('/courses/last-school', 'CourseController@getLastSchool');
    Route::get('/courses/to-reset/{operator_text}/{num_days}', 'CourseController@getCoursesToReset');
    Route::get('/courses/assignments', 'CourseController@getCoursesAndAssignments');
    Route::get('/courses/assignments/non-beta', 'CourseController@getCoursesAndNonBetaAssignments');
    Route::get('/courses/enrolled-in-courses-and-assignments', 'CourseController@getEnrolledInCoursesAndAssignments');

    Route::get('/courses/public/{instructor?}', 'CourseController@getPublicCourses');
    Route::get('/courses/importable', 'CourseController@getImportable');
    Route::patch('/courses/order', 'CourseController@order');
    Route::patch('/courses/{course}/auto-update-question-revisions', 'CourseController@autoUpdateQuestionRevisions');
    Route::patch('/courses/{course}/link-to-lms', 'CourseController@linkToLMS');
    Route::patch('/courses/{course}/unlink-from-lms', 'CourseController@unlinkFromLMS');


    Route::post('/courses/import/{course}', 'CourseController@import');
    Route::get('/courses/beta-approval-notifications/{course}', 'CourseController@getBetaApprovalNotifications');
    Route::patch('/courses/beta-approval-notifications/{course}', 'CourseController@updateBetaApprovalNotifications');

    Route::get('/courses/anonymous-user', 'CourseController@getAnonymousUserCourses');
    Route::get('/courses/commons-courses-and-assignments', 'CourseController@getCommonsCoursesAndAssignments');


    Route::get('/account-customizations', 'AccountCustomizationController@show');
    Route::patch('/account-customizations', 'AccountCustomizationController@update');

    Route::get('/courses/{course}', 'CourseController@show');
    Route::get('/courses/open/{course}', 'CourseController@showOpen');

    Route::patch('/courses/{course}/show-course/{shown}', 'CourseController@showCourse');

    Route::post('/courses', 'CourseController@store');
    Route::patch('/courses/{course}/students-can-view-weighted-average', 'CourseController@updateStudentsCanViewWeightedAverage');
    Route::patch('/courses/{course}/show-z-scores', 'CourseController@updateShowZScores');


    Route::patch('/courses/{course}/show-progress-report', 'CourseController@updateShowProgressReport');
    Route::patch('/courses/{course}', 'CourseController@update');
    Route::delete('/courses/{course}', 'CourseController@destroy');
    Route::delete('/courses/{course}/reset', 'CourseController@reset');

    Route::patch('/assignments/{course}/order', 'AssignmentsController@order');

    Route::post('/breadcrumbs', 'BreadcrumbController@index');

    Route::get('/grading-styles', 'GradingStyleController@index');

    Route::get('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@index');
    Route::patch('/assignmentGroupWeights/{course}', 'AssignmentGroupWeightController@update');

    Route::patch('rubric-category-submissions/{rubricCategory}/assignment/{assignment}/question/{question}', 'RubricCategorySubmissionController@store');
    Route::get('rubric-category-submissions/assignment/{assignment}/question/{question}/user/{user}', 'RubricCategorySubmissionController@getByAssignmentQuestionAndUser');
    Route::patch('rubric-category-submissions/custom/{rubricCategorySubmission}', 'RubricCategorySubmissionController@updateCustom');

    Route::post('rubric-category-submissions/{rubricCategorySubmission}/test-rubric-criteria', 'RubricCategorySubmissionController@testRubricCriteria');

    Route::patch('/assignments/{assignment}/questions/{question}/custom-title', 'AssignmentSyncQuestionController@updateCustomTitle');



    Route::post('rubric-category-custom-criteria', 'RubricCategoryCustomCriteriaController@store');


    Route::get('assignmentGroups', 'AssignmentGroupController@getAssignmentGroupsByUser');
    Route::get('assignmentGroups/{course}', 'AssignmentGroupController@getAssignmentGroupsByCourse');
    Route::get('assignmentGroups/assignment-level/{course}', 'AssignmentGroupController@getAssignmentGroupsByCourseAndAssignment');
    Route::post('assignmentGroups/{course}', 'AssignmentGroupController@store');
    Route::get('assignmentGroups/get-assignment-group-filter/{course}', 'AssignmentGroupController@getAssignmentGroupFilter');

    Route::post('/passback-by-assignment/{assignment}', 'PassbackByAssignmentController@store');


    Route::get('/assignments/download-users-for-assignment-override/{assignment}', 'AssignmentController@downloadUsersForAssignmentOverride');
    Route::get('/assignments/options/{course}', 'AssignmentController@getAssignmentOptions');

    Route::patch('/assignments/{course}/order', 'AssignmentController@order');
    Route::get('/assignments/importable-by-user/{course}', 'AssignmentController@getImportableAssignmentsByUser');
    Route::post('/assignments/import/{assignment}/to/{course}', 'AssignmentController@importAssignment');
    Route::get('/assignments/validate-not-weighted-points-per-question-with-submissions/{assignment}', 'AssignmentController@validateNotWeightedPointsPerQuestionWithSubmissions');




    Route::get('/assignments/courses/{course}', 'AssignmentController@index');
    Route::get('/assignments/courses/{course}/anonymous-user', 'AssignmentController@getAssignmentsForAnonymousUser');

    Route::get('/assignments/courses/public/{course}/names', 'AssignmentController@getAssignmentNamesForPublicCourse');
    Route::get('/assignments/{assignment}/get-questions-info', 'AssignmentController@getQuestionsInfo');
    Route::get('/assignments/{assignment}/summary', 'AssignmentController@getAssignmentSummary');
    Route::get('/assignments/{assignment}/scores-info', 'AssignmentController@scoresInfo');
    Route::get('/assignments/{assignment}/view-questions-info', 'AssignmentController@viewQuestionsInfo');
    Route::get('/assignments/{assignment}/get-info-for-grading', 'AssignmentController@getInfoForGrading');
    Route::post('/assignments/{assignment}/validate-assessment-type', 'AssignmentController@validateAssessmentType');
    Route::get('/assignments/{assignment}/start-page-info', 'AssignmentController@startPageInfo');


    Route::post('/sso/finish-registration', 'Auth\SSOController@finishRegistration');
    Route::post('sso/finish-clicker-app-sso-registration', 'Auth\SSOController@finishClickerAppRegistration');
    Route::get('/sso/completed-registration', 'Auth\SSOController@completedRegistration');
    Route::get('/sso/is-sso-user', 'Auth\SSOController@isSSOUser');

    Route::post('/assignments', 'AssignmentController@store');
    Route::post('/assignments/{assignment}/create-assignment-from-template', 'AssignmentController@createAssignmentFromTemplate');
    Route::patch('/assignments/{assignment}/show-assignment-statistics/{showAssignmentStatistics}', 'AssignmentController@showAssignmentStatistics');
    Route::patch('/assignments/{assignment}/show-scores/{showScores}', 'AssignmentController@showScores');
    Route::patch('/assignments/{assignment}/question-url-view', 'AssignmentController@questionUrlView');
    Route::patch('/assignments/{assignment}/unlink-lti', 'AssignmentController@unlinkLti');
    Route::patch('/assignments/{assignment}/link-to-lms', 'AssignmentController@linkToLMS');

    Route::patch('/assignments/{assignment}/graders-can-see-student-names/{gradersCanSeeStudentNames}', 'AssignmentController@gradersCanSeeStudentNames');
    Route::patch('/assignments/{assignment}/show-points-per-question/{showPointsPerQuestion}', 'AssignmentController@showPointsPerQuestion');
    Route::patch('/assignments/{assignment}/solutions-released/{solutionsReleased}', 'AssignmentController@solutionsReleased');

    Route::patch('/assignments/{assignment}/show-question-titles', 'AssignmentController@showQuestionTitles');

    Route::patch('/assignments/{assignment}/show-assignment/{shown}', 'AssignmentController@showAssignment');
    Route::patch('/assignments/{assignment}/common-question-text', 'AssignmentController@updateCommonQuestionText');
    Route::get('/assignments/{assignment}/common-question-text', 'AssignmentController@showCommonQuestionText');

    Route::patch('/assignments/{assignment}', 'AssignmentController@update');
    Route::delete('/assignments/{assignment}', 'AssignmentController@destroy');

    Route::post('/s3/pre-signed-url', 'S3Controller@preSignedURL');

    Route::patch('/contact-grader-overrides/{course}', 'ContactGraderOverrideController@update');
    Route::get('/contact-grader-overrides/{assignment}', 'ContactGraderOverrideController@show');

    Route::get('/auto-graded-and-file-submissions/{assignment}/{question}/get-auto-graded-and-file-submissions-by-assignment-and-question-and-student', 'AutoGradedAndFileSubmissionController@getAutoGradedAndFileSubmissionsByAsssignmentAndQuestionAndStudent');
    Route::get('/auto-graded-submissions/{assignment}/get-auto-graded-submissions-by-assignment/{download}', 'AutoGradedAndFileSubmissionController@getAutoGradedSubmissionsByAssignment');

    Route::get('/scores/{assignment}/{question}/get-scores-by-assignment-and-question', 'ScoreController@getScoresByAssignmentAndQuestion');
    Route::put('/scores/{assignment}/upload-override-scores', 'ScoreController@uploadOverrideScores');
    Route::post('/scores/over-total-points/{assignment}/{question}', 'ScoreController@overTotalPoints');
    Route::patch('/scores/override-scores/{assignment}', 'ScoreController@overrideScores');
    Route::get('/scores/{course}/get-course-scores-by-user', 'ScoreController@getCourseScoresByUser');
    Route::get('/scores/assignment-user/{assignment}/{user}', 'ScoreController@getScoreByAssignmentAndStudent');
    Route::get('/scores/assignment/get-assignment-questions-scores-by-user/{assignment}/{time_spent_option}/{download}', 'ScoreController@getAssignmentQuestionScoresByUser');
    Route::get('/scores/summary/{assignment}/{question}', 'ScoreController@getScoresByAssignmentAndQuestion');
    Route::get('/scores/{course}/{sectionId}/{download}', 'ScoreController@index');
    Route::get('/scores/tester-student-results/course/{course}/assignment/{assignmentId}', 'ScoreController@testerStudentResults');
    Route::patch('/scores/{assignment}/{user}', 'ScoreController@update');//just doing a patch here because "no score" is consider a score


    Route::post('case-study-notes/unsaved-changes', 'CaseStudyNoteController@getUnsavedChanges');
    Route::post('case-study-notes/save-all', 'CaseStudyNoteController@saveAll');
    Route::get('/case-study-notes/{assignment}', 'CaseStudyNoteController@show');
    Route::patch('/case-study-notes/{assignment}', 'CaseStudyNoteController@update');
    Route::post('/case-study-notes/{assignment}', 'CaseStudyNoteController@store');
    Route::delete('/case-study-notes/assignment/{assignment}', 'CaseStudyNoteController@resetAssignmentNotes');
    Route::delete('/case-study-notes/assignment/{assignment}/type/{type}', 'CaseStudyNoteController@destroyType');
    Route::delete('/case-study-notes/{caseStudyNote}', 'CaseStudyNoteController@destroy');

    Route::get('/case-study-notes/assignment/{assignment}/question/{question_id}', 'AssignmentQuestionSyncCaseStudyNotesController@index');

    Route::get('/analytics-dashboard/{course}', 'AnalyticsDashboardController@show');


    Route::get('/scores/get-ferpa-mode', 'ScoreController@getFerpaMode');

    Route::get('/extensions/{assignment}/{user}', 'ExtensionController@show');
    Route::post('/extensions/{assignment}/{user}', 'ExtensionController@store');


    Route::get('/cutups/{assignment}', 'CutupController@show');
    Route::patch('/cutups/{assignment}/{question}/solution', 'CutupController@updateSolution');

    Route::get('/tags', 'TagController@index');

    Route::post('/meta-tags/admin-view/{adminView}/{perPage}/{currentPage}', 'MetaTagController@getMetaTagsByFilter');
    Route::patch('/meta-tags', 'MetaTagController@update');

    Route::get('/beta-courses/get-alpha-course-from-beta-course/{beta_course}', 'BetaCourseController@getAlphaCourseFromBetaCourse');
    Route::get('/beta-courses/get-from-alpha-course/{alpha_course}', 'BetaCourseController@getBetaCoursesFromAlphaCourse');
    Route::get('/beta-courses/get-tethered-to-alpha-course/{course}', 'BetaCourseController@getTetheredToAlphaCourse');
    Route::delete('/beta-courses/untether/{course}', 'BetaCourseController@untetherBetaCourseFromAlphaCourse');

    Route::post('/beta-courses/do-not-show-beta-course-dates-warning', 'BetaCourseController@doNotShowBetaCourseDatesWarning');

    Route::get('/beta-course-approvals/assignment/{assignment}', 'BetaCourseApprovalController@getByAssignment');
    Route::get('/beta-course-approvals/course/{course}', 'BetaCourseApprovalController@getByCourse');


    Route::get('/whitelisted-domains/{course}', 'WhitelistedDomainController@getByCourse');
    Route::post('/whitelisted-domains/{course}', 'WhitelistedDomainController@store');
    Route::delete('/whitelisted-domains/{whitelistedDomain}', 'WhitelistedDomainController@destroy');


    Route::get('/libreverse/{questionId}/student-learning-objectives', 'LibreverseController@getStudentLearningObjectiveByQuestionId');
    Route::get('/libreverse/library/{library}/page/{pageId}/title', 'LibreverseController@getTitleByLibraryAndPageId');
    Route::post('/libreverse/library/titles', 'LibreverseController@getTitles');

    Route::get('/learning-trees', 'LearningTreeController@index');
    Route::post('/learning-trees/all', 'LearningTreeController@getAll');
    Route::get('/learning-trees/{learningTree}', 'LearningTreeController@show');
    Route::post('/learning-trees/clone', 'LearningTreeController@clone');
    Route::post('/learning-trees/{learningTree}/create-learning-tree-from-template', 'LearningTreeController@createLearningTreeFromTemplate');


    Route::patch('/learning-tree-histories/{learningTree}', 'LearningTreeHistoryController@updateLearningTreeFromHistory');


    Route::post('/learning-trees/learning-tree-exists', 'LearningTreeController@learningTreeExists');
    Route::delete('/learning-trees/{learningTree}', 'LearningTreeController@destroy');
    Route::patch('/learning-trees/{learningTree}', 'LearningTreeController@updateLearningTree');
    Route::post('/learning-trees/info', 'LearningTreeController@storeLearningTreeInfo');
    Route::post('/learning-trees/info/{learningTree}', 'LearningTreeController@updateLearningTreeInfo');


    Route::get('/learning-tree-node-assignment-question/assignment/{assignment}/learning-tree/{learningTree}/completion-info', 'LearningTreeNodeAssignmentQuestionController@learningTreeNodeCompletionInfo');
    Route::post('/learning-tree-node-assignment-question/assignment/{assignment}/learning-tree/{learningTree}/question/{nodeQuestion}/give-credit-for-completion', 'LearningTreeNodeAssignmentQuestionController@giveCreditForCompletion');
    Route::get('/learning-tree-node-assignment-question/assignment/{assignment}/learning-tree/{learningTree}/question/{nodeQuestion}', 'LearningTreeNodeAssignmentQuestionController@show');

    Route::get('/learning-tree-node-submission/{learningTreeNodeSubmission}', 'LearningTreeNodeSubmissionController@show');
    Route::post('/store', 'DataShopController@store');

    Route::get('/learning-trees/validate-remediation-by-assignment-question-id/{assignmentQuestionId}/{isRootNode}', 'LearningTreeController@validateRemediationByAssignmentQuestionId');

    Route::get('/sections/can-create-student-access-codes', 'SectionController@canCreateStudentAccessCodes');
    Route::get('/sections/{course}', 'SectionController@index');
    Route::get('/sections/real-enrolled-users/{section}', 'SectionController@realEnrolledUsers');
    Route::post('/sections/{course}', 'SectionController@store');
    Route::patch('/sections/{section}', 'SectionController@update');
    Route::delete('/sections/{section}', 'SectionController@destroy');
    Route::patch('/sections/refresh-access-code/{section}', 'SectionController@refreshAccessCode');

    Route::patch('/submission-score-overrides', 'SubmissionScoreOverrideController@update');


    Route::patch('/assignments/{assignment}/questions/{question}/iframe-properties', 'AssignmentSyncQuestionController@updateIFrameProperties');
    Route::post('/assignments/{assignment}/questions/{question}/init-refresh-question', 'QuestionController@initRefreshQuestion');
    Route::get('/questions/{question}/assignment-status', 'QuestionController@getAssignmentStatus');
    Route::get('/questions/{question}/question-revision/{questionRevisionId}/rubric-categories', 'QuestionController@getRubricCategories');
    Route::get('/questions/non-meta-properties', 'QuestionController@getNonMetaProperties');

    Route::get('/assignments/{assignment}/questions/{question}/rubric-categories', 'AssignmentSyncQuestionController@getRubricCategoriesByAssignmentAndQuestion');

    Route::get('/questions', 'QuestionController@index');
    Route::get('/questions/default-import-library', 'QuestionController@getDefaultImportLibrary');
    Route::get('/questions/properties/{question}', 'QuestionController@getProperties');
    Route::get('/questions/compare-cached-and-non-cached/{question}', 'QuestionController@compareCachedAndNonCachedQuestions');
    Route::get('/questions/valid-licenses', 'QuestionController@getValidLicenses');
    Route::post('/questions/question-types', 'QuestionController@getQuestionTypes');

    Route::put('/questions/validate-bulk-import-questions', 'QuestionController@validateBulkImportQuestions');
    Route::get('questions/get-question-to-edit/{question}', 'QuestionController@getQuestionToEdit');
    Route::post('/questions/get-webwork-code-from-file-path', 'QuestionController@getWebworkCodeFromFilePath');
    Route::post('/questions/clone', 'QuestionController@clone');

    Route::post('/questions/qti-answer-json', 'QuestionController@getQtiAnswerJson');

    Route::get('/questions/export-webwork-code/{question}', 'QuestionController@exportWebworkCode');

    Route::post('/submission-confirmations/assignment/{assignment}/question/{question}', 'SubmissionConfirmationController@store');

    Route::get('/webwork/list', 'WebworkController@list');
    Route::get('/webwork/clone-dir', 'WebworkController@cloneDir');
    Route::get('/webwork/delete', 'WebworkController@delete');
    Route::post('/webwork/src-doc/assignment/{assignment}/question/{question}', 'WebworkController@getSrcDoc');
    Route::get('/webwork/templates', 'WebworkController@templates');

    Route::get('/unconfirmed-submissions/assignment/{assignment}/question/{question}', 'UnconfirmedSubmissionController@show');
    Route::post('/unconfirmed-submissions/assignment/{assignment}/question/{question}/store-submission', 'UnconfirmedSubmissionController@storeSubmission');

    Route::put('/webwork-attachments/upload', 'WebworkAttachmentController@upload');
    Route::get('/webwork-attachments/question/{question}/{question_revision_id}', 'WebworkAttachmentController@getWebworkAttachmentsByQuestion');
    Route::post('/webwork-attachments/destroy', 'WebworkAttachmentController@destroyWebworkAttachmentByQuestion');

    Route::post('/qti-job/status', 'QtiJobController@getStatus');


    Route::get('/qti-testing/matching', 'QtiTestingController@matching');
    Route::get('/qti-testing/simple-choice', 'QtiTestingController@simpleChoice');
    Route::get('/qti-testing/simple-choice-without-var-equal', 'QtiTestingController@simpleChoiceWithoutVarEqual');
    Route::get('/qti-testing/numerical', 'QtiTestingController@numerical');

    Route::post('/qti-import', 'QtiImportController@store');
    Route::get('/qti-import/clean-up', 'QtiImportController@cleanUp');
    Route::post('/learning-tree-node/reset-root-node-submission/assignment/{assignment}/question/{question}', 'LearningTreeNodeController@resetRootNodeSubmission');
    Route::get('/learning-tree-node/meta-info/{learning_tree}/{question_id}', 'LearningTreeNodeController@getMetaInfo');
    Route::patch('/learning-trees/nodes/{learningTree}', 'LearningTreeNodeController@updateNode');

    Route::post('/branches/descriptions', 'BranchController@getDescriptions');

    Route::get('/questions/{question}', 'QuestionController@show');
    Route::get('/questions/{library}/{page_id}', 'QuestionController@getQuestionByLibraryAndPageId');


    Route::post('/questions', 'QuestionController@store');
    Route::post('/questions/preview', 'QuestionController@preview');
    Route::post('/questions/h5p/{h5p}', 'QuestionController@storeH5P');

    Route::patch('/review-history/assignment/{assignment}/question/{question}', 'ReviewHistoryController@update');

    Route::patch('/report-toggles/assignment/{assignment}/question/{question}/{item}', 'ReportToggleController@update');
    Route::get('/report-toggles/assignment/{assignment}/question/{question}/{item}', 'ReportToggleController@show');

    Route::patch('/questions/{question}', 'QuestionController@update');
    Route::delete('/questions/{question}', 'QuestionController@destroy');

    Route::post('/questions/{question}/refresh/{assignment?}', 'QuestionController@refresh');
    Route::post('/questions/set-question-updated-at-session', 'QuestionController@setQuestionUpdatedAtSession');
    Route::post('/questions/default-import-library', 'QuestionController@storeDefaultImportLibrary');
    Route::post('/questions/{assignment}/direct-import-question', 'QuestionController@directImportQuestion');
    Route::patch('/questions/{question}/refresh-properties', 'QuestionController@refreshProperties');
    Route::patch('/questions/properties/{question}', 'QuestionController@updateProperties');

    Route::post('/question-bank/potential-questions-with-course-level-usage-info', 'QuestionBankController@getQuestionsWithCourseLevelUsageInfo');
    Route::post('/question-bank/all', 'QuestionBankController@getAll');


    Route::get('/metrics/{download}', 'MetricsController@index');
    Route::get('/metrics/cell-data/{download}', 'MetricsController@cellData');

    Route::get('/assignment-templates', 'AssignmentTemplateController@index');
    Route::get('/assignment-templates/{assignmentTemplate}', 'AssignmentTemplateController@show');
    Route::post('/assignment-templates', 'AssignmentTemplateController@store');
    Route::patch('/assignment-templates/order', 'AssignmentTemplateController@order');
    Route::patch('/assignment-templates/{assignmentTemplate}', 'AssignmentTemplateController@update');
    Route::patch('/assignment-templates/copy/{assignmentTemplate}', 'AssignmentTemplateController@copy');
    Route::delete('/assignment-templates/{assignmentTemplate}', 'AssignmentTemplateController@destroy');

    Route::patch('patient-information/show-patient-updated-information/{assignment}', 'PatientInformationController@updateShowPatientUpdatedInformation');
    Route::patch('patient-information/{assignment}', 'PatientInformationController@update');
    Route::get('patient-information/{assignment}', 'PatientInformationController@show');
    Route::delete('patient-information/{assignment}', 'PatientInformationController@destroy');
    Route::patch('patient-information/delete-updated-information/{assignment}', 'PatientInformationController@deleteUpdatedPatientInformation');

    Route::get('/assignments/{assignment}/{question}/last-submitted-info', 'AssignmentSyncQuestionController@updateLastSubmittedAndLastResponse');
    Route::get('/assignments/{assignment}/questions/ids', 'AssignmentSyncQuestionController@getQuestionIdsByAssignment');
    Route::get('/assignments/{assignment}/questions/question-info', 'AssignmentSyncQuestionController@getQuestionInfoByAssignment');
    Route::get('/assignments/{assignment}/questions/view', 'AssignmentSyncQuestionController@getQuestionsToView');
    Route::get('/assignments/{assignment}/questions/summary', 'AssignmentSyncQuestionController@getQuestionSummaryByAssignment');
    Route::patch('/assignments/{assignment}/remix-assignment-with-chosen-questions', 'AssignmentSyncQuestionController@remixAssignmentWithChosenQuestions');

    Route::get('/assignments/{assignment}/validate-can-switch-to-compiled-pdf', 'AssignmentSyncQuestionController@validateCanSwitchToCompiledPdf');
    Route::get('/assignments/{assignment}/validate-can-switch-to-or-from-compiled-pdf', 'AssignmentSyncQuestionController@validateCanSwitchToOrFromCompiledPdf');

    Route::post('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@store');
    Route::post('/assignments/{assignment}/learning-trees/{learningTree}', 'AssignmentQuestionSyncLearningTreeController@store');
    Route::get('/assignment-question-learning-tree/assignments/{assignment}/question/{question}/info', 'AssignmentQuestionSyncLearningTreeController@getAssignmentQuestionLearningTreeInfo');
    Route::patch('/assignment-question-learning-tree/assignments/{assignment}/question/{question}', 'AssignmentQuestionSyncLearningTreeController@update');

    Route::get('/assignments/{assignment}/questions/{question}/get-clicker-status', 'AssignmentSyncQuestionController@getClickerStatus');
    Route::post('/assignments/{assignment}/questions/{question}/start-clicker-assessment', 'AssignmentSyncQuestionController@startClickerAssessment');
    Route::post('/assignments/{assignment}/questions/{question}/end-clicker-assessment', 'AssignmentSyncQuestionController@endClickerAssessment');
    Route::patch('/assignments/{assignment}/questions/{question}/reset-clicker-timer', 'AssignmentSyncQuestionController@resetClickerTimer');

    Route::patch('/assignments/{assignment}/questions/{question}/open-ended-default-text', 'AssignmentSyncQuestionController@storeOpenEndedDefaultText');


    Route::patch('/assignments/{assignment}/questions/{question}/update-open-ended-submission-type', 'AssignmentSyncQuestionController@updateOpenEndedSubmissionType');
    Route::get('/assignments/{assignment}/questions/{question}/has-non-scored-submission-files', 'AssignmentSyncQuestionController@hasNonScoredSubmissionFiles');


    Route::patch('/assignments/{assignment}/questions/{question}/update-points', 'AssignmentSyncQuestionController@updatePoints');
    Route::patch('/assignments/{assignment}/questions/{question}/update-weight', 'AssignmentSyncQuestionController@updateWeight');
    Route::patch('/assignments/{assignment}/questions/{question}/update-completion-scoring-mode', 'AssignmentSyncQuestionController@updateCompletionScoringMode');

    Route::patch('/assignments/{assignment}/questions/order', 'AssignmentSyncQuestionController@order');

    Route::get('/question-revisions/question/{question}', 'QuestionRevisionController@getRevisionsByQuestion');
    Route::get('/question-revisions/{questionRevision}', 'QuestionRevisionController@show');
    Route::get('/question-revisions/{questionRevision}/assignment/{assignment}/question/{question}/update-info', 'QuestionRevisionController@getUpdateRevisionInfo');
    Route::post('/question-revisions/email-students-with-submissions', 'QuestionRevisionController@emailStudentsWithSubmissions');

    Route::patch('/assignments/{assignment}/question/{question}/update-to-latest-revision', 'AssignmentSyncQuestionController@updateToLatestRevision');
    Route::get('/pending-question-revisions/{questionRevision}', 'PendingQuestionRevisionController@show');

    Route::get('/refresh-question-requests', 'RefreshQuestionRequestController@index');
    Route::post('/refresh-question-requests/deny/{question}', 'RefreshQuestionRequestController@denyRefreshQuestionRequest');

    Route::post('/refresh-question-requests/make-refresh-question-request/{question}', 'RefreshQuestionRequestController@makeRefreshQuestionRequest');


    Route::delete('/assignments/{assignment}/questions/{question}', 'AssignmentSyncQuestionController@destroy');


    Route::post('/submission-audios/audio-feedback/{user}/{assignment}/{question}', 'SubmissionAudioController@storeAudioFeedback');
    Route::post('/submission-audios/{assignment}/{question}', 'SubmissionAudioController@store');
    Route::post('/submission-audios/error', 'SubmissionAudioController@logError');


    Route::get('/assignment-grader-access/{assignment}', 'AssignmentGraderAccessController@index');
    Route::patch('assignment-grader-access/{assignment}/{grader}/{access_level}', 'AssignmentGraderAccessController@updateGrader');
    Route::patch('assignment-grader-access/{assignment}/{access_level}', 'AssignmentGraderAccessController@updateAllGraders');


    Route::get('/enrollments', 'EnrollmentController@index');
    Route::get('/enrollments/{course}/details', 'EnrollmentController@details');
    Route::patch('/enrollments/a11y-redirect', 'EnrollmentController@updateA11yRedirect');


    Route::get('/enrollments/{assignment}/from-assignment', 'EnrollmentController@enrollmentsFromAssignment');


    Route::get('/submission-overrides/{assignment}', 'SubmissionOverrideController@index');
    Route::patch('/submission-overrides/{assignment}', 'SubmissionOverrideController@update');
    Route::delete('/submission-overrides/{assignment}/{studentUser}/{type}/{question?}', 'SubmissionOverrideController@destroy');

    Route::patch('/learning-tree-time-left/get-time-left', 'LearningTreeTimeLeftController@getTimeLeft');
    Route::patch('/learning-tree-time-left', 'LearningTreeTimeLeftController@update');

    Route::post('/enrollments', 'EnrollmentController@store');
    Route::post('/enrollments/auto-enroll/{course}/{assignmentId}', 'EnrollmentController@autoEnrollStudent');

    Route::delete('/enrollments/{section}/{user}', 'EnrollmentController@destroy');
    Route::patch('/enrollments/{course}/{user}', 'EnrollmentController@update');

    Route::get('/submissions/can-submit/assignment/{assignment}/question/{question}', 'SubmissionController@canSubmit');
    Route::get('/submissions/submission-array/assignment/{assignment}/question/{question}', 'SubmissionController@submissionArray');

    Route::patch('/submissions/{assignment}/{question}/scores', 'SubmissionController@updateScores');
    Route::patch('/submissions/assignments/{assignment}/question/{question}/reset-submission', 'SubmissionController@resetSubmission');

    Route::patch('/submissions/time-spent/assignment/{assignment}/question/{question}', 'AssignmentQuestionTimeOnTaskController@update');
    Route::patch('/assignment-question-time-spents/assignment/{assignment}/question/{question}', 'AssignmentQuestionTimeOnTaskController@update');
    Route::get('/assignment-question-time-spents/assignment/{assignment}', 'AssignmentQuestionTimeOnTaskController@getTimeOnTasksByAssignment');

    Route::patch('/submissions/time-on-task/assignment/{assignment}/question/{question}', 'AssignmentQuestionTimeOnTaskController@update');
    Route::patch('/assignment-question-time-on-tasks/assignment/{assignment}/question/{question}', 'AssignmentQuestionTimeOnTaskController@update');
    Route::get('/assignment-question-time-on-tasks/assignment/{assignment}', 'AssignmentQuestionTimeOnTaskController@getTimeOnTasksByAssignment');

    Route::get('/users-with-no-role', 'UsersWithNoRoleController@index');
    Route::patch('/users-with-no-role/{user}', 'UsersWithNoRoleController@update');
    Route::delete('/users-with-no-role/{user}', 'UsersWithNoRoleController@destroy');

    Route::get('/users/set-cookie-user-jwt', 'UserController@setCookieUserJWT');

    Route::post('/submissions', 'SubmissionController@store');
    Route::get('/submissions/{assignment}/questions/{question}/pie-chart-data', 'SubmissionController@submissionPieChartData');

    Route::post('/shown-hints/assignments/{assignment}/question/{question}', 'ShownHintController@store');


    Route::get('/canned-responses', 'CannedResponseController@index');
    Route::post('/canned-responses', 'CannedResponseController@store');
    Route::delete('/canned-responses/{cannedResponse}', 'CannedResponseController@destroy');

    Route::get('/submission-files/ungraded-submissions/{course}', 'SubmissionFileController@getUngradedSubmissions');
    Route::patch('/submission-files/{assignment}/{question}/page', 'SubmissionFileController@updatePage');
    Route::patch('/submission-files/{assignment}/{question}/scores', 'SubmissionFileController@updateScores');
    Route::get('/assignment-files/assignment-file-info-by-student/{assignment}', 'AssignmentFileController@getAssignmentFileInfoByStudent');
    Route::post('/submission-files/get-files-from-s3/{assignment}/{question}/{studentUser}', 'SubmissionFileController@getFilesFromS3');
    Route::post('/submission-files/can-submit-file-submission', 'SubmissionFileController@canSubmitFileSubmission');


    Route::post('/solutions/show-solution/{assignment}/{question}', 'SolutionController@showSolutionByAssignmentQuestionUser');
    Route::post('/solutions/text/{assignment}/{question}', 'SolutionController@storeText');
    Route::post('/solution-files/audio/{assignment}/{question}', 'SolutionController@storeAudioSolutionFile');
    Route::put('/solution-files', 'SolutionController@storeSolutionFile');
    Route::post('/solution-files/download', 'SolutionController@downloadSolutionFile');
    Route::delete('/solution-files/{assignment}/{question}', 'SolutionController@destroy');

    Route::post('/submission-texts', 'SubmissionTextController@store');
    Route::delete('/submission-texts/{assignment}/{question}', 'SubmissionTextController@destroy');

    Route::put('/submission-files/file-feedback', 'SubmissionFileController@storeFileFeedback');

    Route::post('/grading', 'GradingController@store');
    Route::get('/grading/{assignment}/{question}/{sectionId}/{gradeView}', 'GradingController@index');


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

    Route::get('/courses/anonymous-user/can-log-in', 'CourseController@canLogInAsAnonymousUser');
    Route::get('/courses/open/index', 'CourseController@open');
    Route::get('/courses/{course}/can-log-into-course-as-anonymous-user', 'CourseController@canLogIntoCourseAsAnonymousUser');

    Route::post('login', 'Auth\LoginController@login');
    Route::post('register', 'Auth\RegisterController@register');

    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    Route::post('email/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('email/resend', 'Auth\VerificationController@resend');

    Route::post('oauth/{driver}', 'Auth\OAuthController@redirectToProvider');
    Route::get('oauth/{driver}/callback', 'Auth\OAuthController@handleProviderCallback')->name('oauth.callback');
});
