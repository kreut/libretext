<?php

namespace App\Http\Controllers;

use App\Framework;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;

use \Exception;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class BreadcrumbController extends Controller
{
    public function index(Request $request)
    {
        $name = $request->name;
        $params = $request->params;
        $course_id = $params['courseId'] ?? 0;
        $assignment_id = $params['assignmentId'] ?? 0;
        $framework_id = $params['frameworkId'] ?? 0;
        $course = $assignment = $framework = null;
        if ($course_id) {
            $course = Course::find($course_id);
        }
        if ($assignment_id) {
            $assignment = Assignment::find($assignment_id);
        }
        if ($framework_id) {
            $framework = Framework::find($framework_id);
        }
        $users = 'no user type defined';
        switch ($request->user()->role) {
            case(3):
                $users = 'students';
                break;
            case(2):
            case(4):
            case(5):
                $users = 'instructors';
                break;
            case(6):
                $users = 'testers';
                break;
        }
        $response['type'] = 'error';
        $breadcrumbs = [];
        $payload = \JWTAuth::parseToken()->getPayload();
        //Canvas will open in a new window if asked and have the session available
        //For Blackboard, I have to force opening a new window and I use localStorage to determine whether to show this
        try {
            if (request()->user()->formative_student) {
                $breadcrumbs[0] = ['text' => $assignment->name,
                    'href' => "#",
                    'active' => true];
            } else if ($name === 'questions.view' &&
                $request->session()->has('lti_user_id') && Auth::user()->role === 3) {

                $breadcrumbs[] = ['text' => "$assignment->name",
                    'href' => "/students/assignments/$assignment_id/summary"];

                $breadcrumbs[] = ['text' => "View Assessments",
                    'href' => "#",
                    'active' => true];
            } else if (Auth::user()->role === 2 || !$request->session()->has('lti_user_id')) {
                if (Auth::check()) {
                    if (!request()->user()->fake_student) {
                        $breadcrumbs[0] = ['text' => 'My Courses', 'href' => "/$users/courses"];
                    }

                    switch ($name) {
                        case('CourseAnalytics'):
                            $breadcrumbs[0] = ['text' => $course->name . ' Analytics',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('nursing.analytics'):
                            $breadcrumbs[0] = ['text' => 'Analytics',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('lti_canvas_config'):
                            $breadcrumbs[0] = ['text' => 'Canvas Configuration',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('frameworks'):
                            $breadcrumbs[0] = ['text' => 'Frameworks',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('framework.view'):
                            $breadcrumbs[0] = ['text' => 'Frameworks',
                                'href' => "/instructors/frameworks",];
                            $breadcrumbs[1] = ['text' => $framework->title,
                                'href' => "#",
                                'active' => true];
                            break;
                        case('open_courses'):
                            $text = "My Courses";
                            if (isset($params['type'])) {
                                $type = $params['type'];
                                if ($type === 'commons') {
                                    $text = "Commons";
                                } elseif ($type === 'public') {
                                    $text = 'Public Courses';
                                }
                            }
                            $breadcrumbs[0] = ['text' => $text,
                                'href' => "#",
                                'active' => true];
                            break;
                        case('testers.students.results'):
                            $breadcrumbs[0] = ['text' => 'My Courses',
                                'href' => "/testers/courses"];
                            break;
                        case('students.sitemap'):
                        case('instructors.sitemap'):
                            $breadcrumbs[0] = ['text' => 'Sitemap',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('questions.get'):
                        case('learning_trees.get'):
                            if ($assignment_id === 'all-questions') {
                                $breadcrumbs[0] = ['text' => 'Search Questions', 'href' => "#"];
                            } else {
                                $breadcrumbs[] = ['text' => $assignment->course->name,
                                    'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                                $breadcrumbs[] = ['text' => "$assignment->name Information",
                                    'href' => "/instructors/assignments/{$assignment->id}/information",
                                ];
                                $breadcrumbs[] = ['text' => 'Add Assessments',
                                    'href' => "#",
                                    'active' => true];
                            }

                            break;
                        case('course_properties.general_info'):
                        case('course_properties.sections'):
                        case('course_properties.letter_grades'):
                        case('course_properties.tethered_courses'):
                        case('course_properties.graders'):
                        case('course_properties.grader_notifications'):
                        case('course_properties.ungraded_submissions'):
                        case('course_properties.students'):
                        case('course_properties.access_codes'):
                        case('course_properties.a11y_redirect'):
                        case('course_properties.edit_assignment_dates'):
                        case('course_properties.iframe_properties'):
                        case('course_properties.non_updated_question_revisions'):
                        case('course_properties.auto_release'):
                        case('course_properties.reset'):
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments"
                            ];
                            $breadcrumbs[] = ['text' => 'Properties',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('instructors.learning_trees.index'):
                            $breadcrumbs[0] = ['text' => 'My Learning Trees', 'href' => ""];
                            break;
                        case('question.editor'):
                            $breadcrumbs[0] = ['text' => 'Question Editor', 'href' => ""];
                            break;
                        case('students.assignments.anonymous.user.index'):
                            $breadcrumbs[0] = ['text' => '', 'href' => ""];
                            break;
                        case('login.as'):
                            $breadcrumbs[0] = ['text' => 'Login As', 'href' => ""];
                            break;
                        case('refresh.question.requests'):
                            $breadcrumbs[0] = ['text' => 'Refresh Question Requests', 'href' => ""];
                            break;
                        case('lti.integrations'):
                            $breadcrumbs[0] = ['text' => 'LTI Integrations', 'href' => ""];
                            break;
                        case('instructorAccessCodes'):
                            $breadcrumbs[0] = ['text' => 'Instructor Access Codes', 'href' => ""];
                            break;
                        case('WebworkSubmissionErrors'):
                            $breadcrumbs[0] = ['text' => 'Webwork Submission Errors', 'href' => ""];
                            break;
                        case('coursesToReset'):
                            $breadcrumbs[0] = ['text' => 'Courses To Reset', 'href' => ""];
                            break;
                        case('testerAccessCodes'):
                            $breadcrumbs[0] = ['text' => 'Tester Access Codes', 'href' => ""];
                            break;
                        case('LearningTreeAnalytics'):
                            $breadcrumbs[0] = ['text' => 'Learning Tree Analytics', 'href' => ""];
                            break;
                        case('metrics'):
                            $breadcrumbs[0] = ['text' => 'Metrics', 'href' => ""];
                            break;
                        case('updateUserInfo'):
                            $breadcrumbs[0] = ['text' => 'Update User Info', 'href' => ""];
                            break;
                        case('classificationManager'):
                            $breadcrumbs[0] = ['text' => 'Classification Manager', 'href' => ""];
                            break;
                        case('questionEditors'):
                            $breadcrumbs[0] = ['text' => 'Question Editors', 'href' => ""];
                            break;
                        case('all.questions.get'):
                            $breadcrumbs[0] = ['text' => 'Search Questions', 'href' => ""];
                            break;
                        case('all.learning.trees.get'):
                            $breadcrumbs[0] = ['text' => 'Browse Learning Trees', 'href' => "#", 'active' => true];
                            break;
                        case('edit_question'):
                            $breadcrumbs[0] = ['text' => 'Edit Question', 'href' => "#", 'active' => true];
                            break;
                        case('instructors.learning_trees.editor'):
                            $is_author = false;
                            if (isset($params['learningTreeId'])) {
                                $is_author = DB::table('learning_trees')
                                    ->where('id', $params['learningTreeId'])
                                    ->where('user_id', $request->user()->id)
                                    ->first();
                            }
                            if (isset($params['fromAllLearningTrees']) && !$is_author) {
                                $breadcrumbs[0] = ['text' => 'View Learning Tree', 'href' => "#", 'active' => true];
                            } else {
                                $breadcrumbs[0] = ['text' => 'My Learning Trees', 'href' => "/instructors/learning-trees"];
                                $breadcrumbs[1] = ['text' => 'Editor', 'href' => "#", 'active' => true];
                            }
                            break;
                        case('assignments.templates'):
                            $breadcrumbs[0] = ['text' => 'Assignment Templates', 'href' => ""];
                            break;
                        case('settings.profile'):
                        case('settings.password'):
                        case('settings.notifications'):
                        case('settings.account_customizations'):
                        case('settings.linked_accounts'):
                            $breadcrumbs[] = ['text' => 'Settings',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('linked_accounts'):
                            $breadcrumbs[0] = ['text' => 'Linked Accounts',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('notifications'):
                            $breadcrumbs[0] = ['text' => 'Notifications',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('instructors.assignments.index'):
                            //My courses / the assignment's course
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments",
                                'active' => true];
                            break;
                        case('students.assignments.index'):
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/students/courses/{$course->id}/assignments",
                                'active' => true];
                            break;
                        case('students.assignments.summary'):
                        case('instructors.assignments.summary'):
                        case('instructors.assignments.properties'):
                        case('instructors.assignments.control_panel'):
                        case('instructors.assignments.submission_overrides'):
                        case('instructors.assignments.statistics'):
                        case('instructors.assignments.grader_access'):
                        case('instructors.assignments.questions'):
                        case('instructors.assignments.edit_scores'):
                        case('instructors.assignments.gradebook'):
                        case('instructors.assignments.auto_graded_submissions'):
                        case('instructors.assignments.case.study.notes');
                        case('instructors.assignments.lab_report');
                            //My courses / The assignment's course / that assignment;
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                            $breadcrumbs[] = ['text' => $assignment->name,
                                'href' => "#",
                                'active' => true];
                            break;
                        case('questions.view'):
                            //My courses / The assignment's course / that assignment summary / the assignment questions
                            if (Helper::isAnonymousUser()
                                || (Helper::hasAnonymousUserSession() && $assignment->course->user_id !== request()->user()->id)) {
                                $breadcrumbs = [
                                    ['text' => $assignment->course->name,
                                        'href' => "/students/courses/{$assignment->course->id}/assignments/anonymous-user"]
                                ];
                            } else {
                                $breadcrumbs[] = ['text' => $assignment->course->name,
                                    'href' => "/$users/courses/{$assignment->course->id}/assignments"];
                                if (Auth::user()->role === 3) {
                                    $breadcrumbs[] = ['text' => "{$assignment->name}",
                                        'href' => "/students/assignments/{$assignment_id}/summary"];
                                } else {
                                    $breadcrumbs[] = ['text' => "{$assignment->name}",
                                        'href' => "/instructors/assignments/{$assignment_id}/information"];
                                }
                            }
                            $breadcrumbs[] = ['text' => "View Assessments",
                                'href' => "#",
                                'active' => true];
                            break;
//My courses / The assignment's course / that assignment / questions get
                        case
                        ('gradebook.index'):
                            //My courses / that course
                            $breadcrumbs[] = ['text' => $course->name,
                                'href' => "/instructors/courses/{$course->id}/assignments"];
                            $breadcrumbs[] = ['text' => 'Gradebook',
                                'href' => "#",
                                'active' => true];
                            break;
                        case('assignment.mass_grading.index'):
                        case('assignment.grading.index'):
                            $text = $name === 'assignment.grading.index' ? 'Open Grader' : 'Grading';
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                            $breadcrumbs[] = ['text' => $text,
                                'href' => "#",
                                'active' => true];
                            break;
                        case('question.view'):
                            $breadcrumbs[] = ['text' => $assignment->course->name,
                                'href' => "/instructors/courses/{$assignment->course->id}/assignments"];
                            if (in_array($assignment->submission_files, ['q', 'a'])) {
                                $type = $assignment->submission_files === 'q' ? 'question' : 'assignment';
                                $breadcrumbs[] = ['text' => 'Grading',
                                    'href' => "/assignments/{$assignment->id}/$type-files"];
                            }
                            $breadcrumbs[] = ['text' => 'View Assessments',
                                'href' => "#",
                                'active' => true];
                    }
                }
            }
            $response['type'] = 'success';
            $response['breadcrumbs'] = $breadcrumbs;
        } catch
        (Exception $e) {
            //no message for the user: just for me

        }

        return $response;

    }
}
