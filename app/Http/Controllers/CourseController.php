<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignToTiming;
use App\AssignToUser;
use App\BetaAssignment;
use App\BetaCourse;
use App\BetaCourseApproval;
use App\Course;
use App\FinalGrade;
use App\Http\Requests\DestroyCourse;
use App\Http\Requests\ImportCourseRequest;
use App\Http\Requests\ResetCourse;
use App\Jobs\DeleteAssignmentDirectoryFromS3;
use App\School;
use App\Section;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Enrollment;
use App\Http\Requests\StoreCourse;
use App\User;
use App\WhitelistedDomain;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatter;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{

    use DateFormatter;

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getNonBetaCoursesAndAssignments(Request $request, Course $course): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getNonBetaCoursesAndAssignments', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $beta_courses = DB::table('beta_courses')->get('id')->pluck('id')->toArray();
            $courses_and_assignments = DB::table('courses')->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->select('courses.name AS course_name', 'courses.id AS course_id', 'assignments.name AS assignment_name', 'assignments.id AS assignment_id')
                ->where('courses.user_id', $request->user()->id)
                ->whereNotIn('courses.id', $beta_courses)
                ->orderBy('course_name')
                ->orderBy('assignments.order')
                ->get();
            $course_ids = [];
            $courses = [];
            $assignments = [];
            foreach ($courses_and_assignments as $value) {
                if (!in_array($value->course_id, $course_ids)) {
                    $courses[] = ['course_id' => $value->course_id, 'course_name' => $value->course_name];
                    $assignments[$value->course_id] = [];
                    $assignments[$value->course_id]['course_id'] = $value->course_id;
                    $assignments[$value->course_id]['assignments'] = [];
                    $course_ids[] = $value->course_id;
                }
                $assignments[$value->course_id]['assignments'][] = ['assignment_id' => $value->assignment_id, 'assignment_name' => $value->assignment_name];
            }
            $response['type'] = 'success';
            $response['courses'] = $courses;
            $response['assignments'] = array_values($assignments);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your courses and assignments.";
        }
        return $response;

    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getCommonsCoursesAndAssignments(Course $course): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getCommonsCoursesAndAssignments', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $commons_user = User::where('email', 'commons@libretexts.org')->first();
            $commons_courses_and_assignments = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->select('assignments.id AS assignment_id',
                    'courses.id AS course_id',
                    'assignments.name AS assignment_name',
                    'courses.name AS course_name')
                ->where('courses.user_id', $commons_user->id)
                ->orderBy('course_name')
                ->orderBy('assignment_name')
                ->get();
            $response['commons_courses_and_assignments'] = $commons_courses_and_assignments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the Commons courses and assignments.";
        }
        return $response;


    }


    /**
     * @param ResetCourse $request
     * @param Course $course
     * @param AssignToTiming $assignToTiming
     * @return array
     * @throws Exception
     */
    public
    function reset(ResetCourse    $request,
                   Course         $course,
                   AssignToTiming $assignToTiming): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('reset', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $request->validated();

        try {
            DB::beginTransaction();
            $fake_student = DB::table('enrollments')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('course_id', $course->id)
                ->where('fake_student', 1)
                ->first();

            $assignments = $course->assignments;
            $assignment_ids = [];
            foreach ($assignments as $assignment) {
                $assignment_ids[] = $assignment->id;
                $default_timing = $assignToTiming->where('assignment_id', $assignment->id)->first();
                $assignToTiming->deleteTimingsGroupsUsers($assignment);
                $assign_to_timing_id = $assignment->saveAssignmentTimingAndGroup($assignment, $default_timing);
                $assignToUser = new AssignToUser();
                $assignToUser->assign_to_timing_id = $assign_to_timing_id;
                $assignToUser->user_id = $fake_student->user_id;
                $assignToUser->save();
            }

            DB::table('submissions')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('submission_files')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('scores')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('cutups')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('seeds')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('compiled_pdf_overrides')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('question_level_overrides')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('assignment_level_overrides')->whereIn('assignment_id', $assignment_ids)->delete();
            //reset all of the LMS stuff
            DB::table('lti_grade_passbacks')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('lti_launches')->whereIn('assignment_id', $assignment_ids)->delete();
            DB::table('assignments')->whereIn('id', $assignment_ids)
                ->update(['lms_resource_link_id' => null]);


            $course->extensions()->delete();
            $course->extraCredits()->delete();
            DB::table('enrollments')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('course_id', $course->id)
                ->where('fake_student', 0)
                ->delete();
            foreach ($assignments as $assignment) {
                DeleteAssignmentDirectoryFromS3::dispatch($assignment->id);
            }
            DB::commit();

            $response['type'] = 'success';
            $response['message'] = "All students from <strong>$course->name</strong> have been unenrolled and their data removed.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing all students from <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function order(Request $request, Course $course)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$course, $request->ordered_courses]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::beginTransaction();
            $course->orderCourses($request->ordered_courses);
            DB::commit();
            $response['message'] = 'Your courses have been re-ordered.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error re-ordering your courses.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function getAllCourses(Course $course): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAllCourses', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['courses'] = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->select('courses.id AS value', DB::raw('CONCAT(courses.name, " --- " ,first_name, " " , last_name) AS label'))
                ->orderBy('label')
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not get all courses.';
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function open(Course $course): array
    {

        $response['type'] = 'error';
        try {

            $response['open_courses'] = $course->where('anonymous_users', 1)->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not determine whether you can log into the this course as an anonymous user.';
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function canLogIntoCourseAsAnonymousUser(Course $course): array
    {


        try {
            $response['can_log_into_course_as_anonymous_user'] = (boolean)$course->anonymous_users;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['type'] = 'error';
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not determine whether you can log into the this course as an anonymous user.';
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function hasH5PQuestions(Course $course)
    {

        $response['type'] = 'error';
        try {
            $h5p_questions_exist = DB::table('assignment_question')
                ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('assignments.course_id', $course->id)
                ->where('questions.technology', 'h5p')
                ->get()
                ->isNotEmpty();
            $response['h5p_questions_exist'] = $h5p_questions_exist;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not determine whether this course has H5P questions.';
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public
    function canLogInAsAnonymousUser(Request $request, Assignment $assignment): array
    {
        try {
            $response['type'] = 'error';
            $landing_page = $request->session()->get('landing_page');
            if ($landing_page) {
                $landing_page_array = explode('/', $landing_page);
                /*      0 => ""
                        1 => "assignments"
                        2 => "298"
                        3 => "questions"
                        4 => "view"
                        5 => "98505"
                */
                $anonymous_users = false;
                if (count($landing_page_array) === 6
                    && $landing_page_array[1] === 'assignments'
                    && is_numeric($landing_page_array[2])
                    && $landing_page_array[3] === 'questions'
                    && $landing_page_array[4] === 'view'
                    && is_numeric($landing_page_array[5])) {
                    $course = DB::table('assignments')
                        ->join('courses', 'assignments.course_id', '=', 'courses.id')
                        ->where('assignments.id', $landing_page_array[2])
                        ->first();
                    $anonymous_users = (boolean)$course->anonymous_users;
                }
                $response['type'] = 'success';
                $response['anonymous_users'] = $anonymous_users;
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = 'We could not determine whether this course allowed anonymous users.';
        }
        return $response;

    }

    /**
     * @return array
     * @throws Exception
     */
    public
    function getAnonymousUserCourses(): array
    {
        try {
            $response['enrollments'] = DB::table('courses')
                ->where('courses.anonymous_users', 1)
                ->where('courses.shown', 1)
                ->select('id',
                    'courses.name AS course_section_name',
                    'public_description')
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the courses with anonymous users.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function updateIFrameProperties(Request $request, Course $course)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateIFrameProperties', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $item = $request->item;
            if (!in_array($item, ['attribution', 'assignment', 'submission', 'question_numbers'])) {
                $response['message'] = "$item is not a valid iframe property.";
                return $response;
            }
            $action = $request->action;
            if (!in_array($action, ['show', 'hide'])) {
                $response['message'] = "$action isn't a valid action.";
                return $response;
            }
            $value = ($action === 'show') ? 1 : 0;
            $assignments = DB::table('assignments')->where('course_id', $course->id)->get('id');
            $message = "This course has no assignments.";
            $action_message = ($action === 'show') ? 'shown' : 'hidden';
            $type = "info";
            if ($item === 'question_numbers') {
                DB::table('courses')
                    ->where('id', $course->id)
                    ->update(['question_numbers_shown_in_iframe' => !$course->question_numbers_shown_in_iframe]);
                $type = ($action === 'show') ? 'success' : 'info';
                $message = "The question numbers will now be $action_message when embedded in an iframe.";
            } else {
                if ($assignments) {
                    $assignment_ids = $assignments->pluck('id');
                    DB::table('assignment_question')
                        ->whereIn('assignment_id', $assignment_ids)
                        ->update(["{$item}_information_shown_in_iframe" => $value]);
                    $type = ($action === 'show') ? 'success' : 'info';
                    $message = "The $item information will now be $action_message when embedded in an iframe.";
                }
            }
            $response['message'] = $message;
            $response['type'] = $type;
            return $response;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update the iframe properties for your course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @return array
     * @throws Exception
     */
    public
    function getOpenCourses(): array
    {
        $response['type'] = 'error';
        try {
            $commons_user = User::where('email', 'commons@libretexts.org')->first();
            $open_courses = DB::table('courses')
                ->where('courses.user_id', $commons_user->id)
                ->where('shown', 1)
                ->where('anonymous_users', 1)
                ->select('id',
                    'courses.name AS name',
                    'courses.public_description AS description',
                    DB::raw('CONCAT("' . config('app.url') . '/courses/",id,"/anonymous") AS url'))
                ->get();
            $response['open_courses'] = $open_courses;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see get the courses from the Commons.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @return array
     * @throws Exception
     */
    public
    function getCommonsCourses(): array
    {
        $response['type'] = 'error';
        try {
            $commons_user = User::where('email', 'commons@libretexts.org')->first();
            $commons_courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->join('schools', 'courses.school_id', '=', 'schools.id')
                ->where('courses.user_id', $commons_user->id)
                ->where('shown', 1)
                ->select('courses.id',
                    'courses.name AS name',
                    'courses.public_description AS description',
                    'schools.name AS school',
                    DB::raw("CONCAT(users.first_name, ' ',users.last_name) AS instructor"),
                    'alpha',
                    'anonymous_users')
                ->get();
            $response['commons_courses'] = $commons_courses;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see get the courses from the Commons.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function updateBetaApprovalNotifications(Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('updateBetaApprovalNotifications', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $course->beta_approval_notifications = !$course->beta_approval_notifications;
            $course->save();
            $message_text = $course->beta_approval_notifications ? "now" : "no longer";
            $response['type'] = 'info';
            $response['message'] = "You will $message_text receive daily email notifications of pending approvals.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function getBetaApprovalNotifications(Course $course)
    {

        $response['type'] = 'error';
        try {
            $response['beta_approval_notifications'] = $course->beta_approval_notifications;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function getWarnings(Course $course): array
    {
        try {
            $response['alpha'] = $course->alpha;
            $response['formative'] = $course->formative;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the course warnings.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function isAlpha(Course $course)
    {
        try {
            $response['alpha'] = $course->alpha;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see whether this course is an Alpha course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function getLastSchool(Request $request, School $school)
    {
        $response['type'] = 'error';
        try {
            $school_name = '';
            $school_id = 1;
            if ($request->user()->role === 2) {
                $school = DB::table('courses')
                    ->join('schools', 'courses.school_id', '=', 'schools.id')
                    ->where('user_id', $request->user()->id)
                    ->orderBy('courses.created_at', 'desc')
                    ->first();
                if ($school && ($school->school_id !== 1)) {
                    $school_name = $school->name;
                    $school_id = $school->school_id;
                }
            }
            $response['last_school_name'] = $school_name;
            $response['last_school_id'] = $school_id;
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your last school.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Course $course
     * @param User|null $instructor
     * @return array
     * @throws Exception
     */
    public
    function getPublicCourses(Course $course, User $instructor = null): array
    {

        $response['type'] = 'error';
        try {
            $public_courses = [];
            switch ($instructor) {
                case(true):
                    $public_courses = $course->where('public', 1)
                        ->where('user_id', $instructor->id)
                        ->select('id', 'name')
                        ->orderBy('name')
                        ->get();
                    break;
                case(false):
                    $public_courses = $course->publicCourses();
                    break;

            }
            $response['public_courses'] = $public_courses;
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get the public courses.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public
    function getCoursesAndNonBetaAssignments(Request $request)
    {

        $response['type'] = 'error';
        $courses = [];
        $assignments = [];
        try {
            $results = DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->leftJoin('beta_assignments', 'assignments.id', '=', 'beta_assignments.id')
                ->where('courses.user_id', $request->user()->id)
                ->select(DB::raw('courses.id AS course_id'),
                    DB::raw('courses.name AS course_name'),
                    'courses.start_date',
                    'courses.end_date',
                    'beta_assignments.id AS beta_assignment_id',
                    DB::raw('assignments.id AS assignment_id'),
                    DB::raw('assignments.name AS assignment_name'))
                ->orderBy('courses.start_date', 'desc')
                ->get();
            $course_ids = [];
            foreach ($results as $value) {
                $course_id = $value->course_id;
                if (!in_array($course_id, $course_ids)) {
                    $courses[] = ['value' => $course_id,
                        'text' => $value->course_name,
                        'start_date' => $value->start_date,
                        'end_date' => $value->end_date];
                    $course_ids[] = $course_id;
                }
                if (!$value->beta_assignment_id) {
                    $assignments[$course_id][] = ['value' => $value->assignment_id,
                        'text' => $value->assignment_name];
                }
            }

            $response['type'] = 'success';
            $response['courses'] = $courses;
            $response['assignments'] = $assignments;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your courses and assignments.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Enrollment $enrollment
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function getEnrolledInCoursesAndAssignments(Enrollment $enrollment, Course $course): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $enrollments = $enrollment->index();
        $enrolled_in_courses_and_assignments = [];
        foreach ($enrollments as $key => $enrollment) {
            $enrolled_in_courses_and_assignments[$key] = [];
            $enrolled_in_courses_and_assignments[$key]['course'] = $enrollment;
            $course = Course::find($enrollment->id);
            $enrolled_in_courses_and_assignments[$key]['assignments'] = $course->assignedToAssignmentsByUser();
        }

        try {
            $response['enrolled_in_courses_and_assignments'] = $enrolled_in_courses_and_assignments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrolled courses and assignments.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function getCoursesAndAssignments(Request $request)
    {

        $response['type'] = 'error';
        $courses = [];
        $assignments = [];
        try {
            $results = DB::table('courses')
                ->join('assignments', 'courses.id', '=', 'assignments.course_id')
                ->where('courses.user_id', $request->user()->id)
                ->select(DB::raw('courses.id AS course_id'),
                    DB::raw('courses.name AS course_name'),
                    'courses.lms',
                    'assignments.lms_resource_link_id',
                    'assignments.assessment_type',
                    DB::raw('assignments.id AS assignment_id'),
                    DB::raw('assignments.name AS assignment_name'))
                ->orderBy('courses.start_date', 'desc')
                ->get();
            $course_ids = [];
            foreach ($results as $value) {
                $course_id = $value->course_id;
                if (!in_array($course_id, $course_ids)) {
                    $courses[] = ['value' => $course_id,
                        'text' => $value->course_name,
                        'lms' => $value->lms];
                    $course_ids[] = $course_id;
                }
                $assignments[$course_id][] = ['value' => $value->assignment_id,
                    'text' => $value->assignment_name,
                    'lms_resource_link_id' => $value->lms_resource_link_id,
                    'assessment_type' => $value->assessment_type];
            }

            $response['type'] = 'success';
            $response['courses'] = $courses;
            $response['assignments'] = $assignments;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your courses and assignments.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @param AssignmentGroup $assignmentGroup
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @param Section $section
     * @param School $school
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception '
     */
    public
    function import(ImportCourseRequest    $request,
                    Course                 $course,
                    AssignmentGroup        $assignmentGroup,
                    AssignmentGroupWeight  $assignmentGroupWeight,
                    AssignmentSyncQuestion $assignmentSyncQuestion,
                    Enrollment             $enrollment,
                    FinalGrade             $finalGrade,
                    Section                $section,
                    School                 $school,
                    BetaCourse             $betaCourse): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('import', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!in_array($request->action, ['import', 'clone'])) {
            $response['message'] = "$request->action should either be to import or clone.";
            return $response;
        }
        $import_as_beta = (int)$request->import_as_beta;

        if ($import_as_beta && !$course->alpha) {
            $response['message'] = "You cannot $request->action this course as a Beta course since the original course is not an Alpha course.";
            return $response;
        }
        $school = $this->getLastSchool($request, $school);
        try {
            DB::beginTransaction();
            $imported_course = $course->replicate();
            $action = $request->action === 'import' ? "Import" : "Copy";
            $imported_course->name = "$imported_course->name " . $action;
            $imported_course->start_date = Carbon::now()->startOfDay();
            $imported_course->end_date = Carbon::now()->startOfDay()->addMonths(3);
            $imported_course->shown = 0;
            $imported_course->public = 0;
            $imported_course->alpha = 0;
            $imported_course->lms = 0;
            $imported_course->anonymous_users = 0;
            $imported_course->school_id = $school['last_school_id'];
            $imported_course->show_z_scores = 0;
            $imported_course->students_can_view_weighted_average = 0;
            $imported_course->user_id = $request->user()->id;
            $imported_course->order = 0;
            $imported_course->save();
            $course_id = $imported_course->id;
            $whitelistedDomain = new WhitelistedDomain();
            $whitelistedDomain->whitelisted_domain = $whitelistedDomain->getWhitelistedDomainFromEmail($request->user()->email);
            $whitelistedDomain->course_id = $course_id;
            if ($import_as_beta) {
                $betaCourse->id = $imported_course->id;
                $betaCourse->alpha_course_id = $course->id;
                $betaCourse->save();
            }

            $whitelistedDomain->save();
            $minutes_diff = 0;
            if ($request->shift_dates && $course->assignments->isNotEmpty()) {
                $first_assignment = $course->assignments[0];

                $carbon_time = Carbon::createFromFormat('h:i A', $request->due_time)
                    ->format('H:i:00');

                $new_due = $request->due_date . ' ' . $carbon_time;
                $first_assignment_timing = DB::table('assign_to_timings')
                    ->where('assignment_id', $first_assignment->id)
                    ->first();
                $old_due = $first_assignment_timing->due;

                $date1 = Carbon::createFromFormat('Y-m-d H:i:s', $old_due, 'UTC');
                $date2 = Carbon::createFromFormat('Y-m-d H:i:s', $new_due, $request->user()->time_zone);
                $minutes_diff = $date1->diffInMinutes($date2);

            }
            foreach ($course->assignments as $assignment) {
                $imported_assignment = $this->cloneAssignment($assignmentGroup, $imported_course, $assignment, $assignmentGroupWeight, $course);
                if ($import_as_beta) {
                    BetaAssignment::create([
                        'id' => $imported_assignment->id,
                        'alpha_assignment_id' => $assignment->id
                    ]);
                }
                $default_timing = DB::table('assign_to_timings')
                    ->join('assign_to_groups', 'assign_to_timings.id', '=', 'assign_to_groups.assign_to_timing_id')
                    ->where('assignment_id', $assignment->id)
                    ->first();
                foreach (['available_from', 'due', 'final_submission_deadline'] as $time) {
                    if ($default_timing->{$time}) {
                        $carbon_time = Carbon::createFromFormat('Y-m-d H:i:s', $default_timing->{$time});
                        $default_timing->{$time} = $carbon_time->addMinutes($minutes_diff)->format('Y-m-d H:i:s');
                    }
                }

                $assignment->saveAssignmentTimingAndGroup($imported_assignment, $default_timing);
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $imported_assignment->id);
            }

            $this->prepareNewCourse($section, $imported_course, $course, $enrollment, $finalGrade);
            $fake_user = DB::table('enrollments')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('course_id', $imported_course->id)
                ->where('fake_student', 1)
                ->first();

            $assign_to_timings = DB::table('assign_to_timings')
                ->whereIn('assignment_id', $imported_course->assignments->pluck('id')->toArray())
                ->get();
            foreach ($assign_to_timings as $assign_to_timing) {
                $assignToUser = new AssignToUser();
                $assignToUser->assign_to_timing_id = $assign_to_timing->id;
                $assignToUser->user_id = $fake_user->id;
                $assignToUser->save();
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "<strong>$imported_course->name</strong> has been created.  </br></br>Don't forget to change the dates associated with this course and all of its assignments.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating the $request->action.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function reOrderAllCourses()
    {
        $courses = $this->getCourses(Auth::user());
        $all_course_ids = [];
        if ($courses) {
            foreach ($courses as $value) {
                $all_course_ids[] = $value->id;
            }
            $course = new Course();
            $course->orderCourses($all_course_ids);
        }
    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function getImportable(Request $request, Course $course)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('getImportable', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $instructor_courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('user_id', $request->user()->id)
                ->select('name',
                    DB::raw('CONCAT(first_name, " " , last_name) AS instructor'),
                    'courses.id',
                    'term')
                ->get();
            $public_courses = $course->publicCourses();

            $importable_courses = [];
            foreach ($instructor_courses as $course) {
                $importable_courses[] = $course;
            }
            foreach ($public_courses as $course) {
                $importable_courses[] = $course;
            }
            $formatted_importable_courses = [];
            $formatted_course_ids = [];
            $formatted_course_names = [];
            foreach ($importable_courses as $course) {
                if (!in_array($course->id, $formatted_course_ids)) {
                    $formatted_course = "$course->name --- $course->instructor";
                    if (in_array($formatted_course, $formatted_course_names)) {
                        $formatted_course = "$course->name ($course->term) --- $course->instructor";
                    }
                    $formatted_importable_courses[] = [
                        'course_id' => $course->id,
                        'formatted_course' => $formatted_course
                    ];
                    $formatted_course_ids[] = $course->id;
                    $formatted_course_names[] = $formatted_course;
                }

            }

            usort($formatted_importable_courses, function ($item1, $item2) {
                return $item1['formatted_course'] <=> $item2['formatted_course'];
            });

            $response['type'] = 'success';
            $response['importable_courses'] = $formatted_importable_courses;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the importable courses.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function index(Request $request, Course $course)
    {

        $response['type'] = 'error';


        if ($request->session()->get('completed_sso_registration')) {
            \Log::info('Just finished registration.');
        }
        $authorized = Gate::inspect('viewAny', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['courses'] = $this->getCourses(auth()->user());
            $response['show_beta_course_dates_warning'] = !$request->hasCookie('show_beta_course_dates_warning');

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your courses.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */
    public
    function updateShowZScores(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateShowZScores', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response = $assignmentGroupWeight->validateCourseWeights($course);
        if ($response['type'] === 'error') {
            return $response;
        }
        try {

            $course->show_z_scores = !$request->show_z_scores;
            $course->save();

            $verb = $course->show_z_scores ? "can" : "cannot";
            $response['type'] = $course->show_z_scores ? 'success' : 'info';
            $response['message'] = "Students <strong>$verb</strong> view their z-scores.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the ability for students to view their z-scores.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function updateShowProgressReport(Request $request, Course $course): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateShowProgressReport', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $course->show_progress_report = !$request->show_progress_report;
            $course->save();

            $verb = $course->show_progress_report ? "can" : "cannot";
            $response['type'] = $course->show_progress_report ? 'success' : 'info';
            $response['message'] = "Students <strong>$verb</strong> view their progress reports.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the ability for students to view their progress_reports.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function showOpen(Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('viewOpen', $course);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['open_course'] = [
                'id' => $course->id,
                'name' => $course->name,
                'alpha' => $course->alpha];
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving this open course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function updateStudentsCanViewWeightedAverage(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateStudentsCanViewWeightedAverage', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response = $assignmentGroupWeight->validateCourseWeights($course);
            if ($response['type'] === 'error') {
                return $response;
            }
            $course->students_can_view_weighted_average = !$request->students_can_view_weighted_average;
            $course->save();

            $verb = $course->students_can_view_weighted_average ? "can" : "cannot";
            $response['type'] = $course->students_can_view_weighted_average ? 'success' : 'info';
            $response['message'] = "Students <strong>$verb</strong> view their weighted averages.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the ability for students to view their weighted averages.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public
    function show(Course $course, WhitelistedDomain $whitelistedDomain): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $question_exists_not_owned_by_the_instructor = DB::table('assignment_question')
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->whereIn('assignment_id', $course->assignments()->pluck('id')->toArray())
                ->where('question_editor_user_id', '<>', $course->user_id)
                ->first();
            $response['course'] = [
                'school' => $course->school->name,
                'name' => $course->name,
                'public_description' => $course->public_description,
                'private_description' => $course->private_description,
                'textbook_url' => $course->textbook_url,
                'term' => $course->term,
                'students_can_view_weighted_average' => $course->students_can_view_weighted_average,
                'letter_grades_released' => $course->finalGrades->letter_grades_released,
                'sections' => $course->sections,
                'show_z_scores' => $course->show_z_scores,
                'graders' => $course->graderInfo(),
                'start_date' => $course->start_date,
                'end_date' => $course->end_date,
                'public' => $course->public,
                'lms' => $course->lms,
                'question_numbers_shown_in_iframe' => (bool)$course->question_numbers_shown_in_iframe,
                'show_progress_report' => $course->show_progress_report,
                'owns_all_questions' => !$question_exists_not_owned_by_the_instructor,
                'alpha' => $course->alpha,
                'anonymous_users' => $course->anonymous_users,
                'formative' => $course->formative,
                'contact_grader_override' => $course->contactGraderOverride(),
                'is_beta_course' => $course->isBetaCourse(),
                'beta_courses_info' => $course->betaCoursesInfo(),
                'whitelisted_domains' => $whitelistedDomain
                    ->where('course_id', $course->id)
                    ->select('whitelisted_domain')
                    ->pluck('whitelisted_domain')
                    ->toArray()];
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Course $course
     * @param int $shown
     * @return array
     * @throws Exception
     */
    public
    function showCourse(Course $course, int $shown): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showCourse', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $course->shown = !$shown;
            $course->save();

            $response['type'] = !$shown ? 'success' : 'info';
            $shown_message = !$shown ? 'can' : 'cannot';
            $is_commons_user = Auth::user()->email === 'commons@libretexts.org';
            $access_code_message = !$shown || $is_commons_user ? '' : '  In addition, all course access codes have been revoked.';
            $people = $is_commons_user ? "Visitors to the Commons " : "Your students";
            $response['message'] = "$people <strong>{$shown_message}</strong> view this course.{$access_code_message}";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing <strong>{$course->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param $user
     * @return array|\Illuminate\Support\Collection
     */
    public
    function getCourses($user)
    {

        switch ($user->role) {
            case(6):
                return DB::table('tester_courses')
                    ->join('courses', 'tester_courses.course_id', '=', 'courses.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->where('tester_courses.user_id', $user->id)
                    ->select('courses.id',
                        'term',
                        'start_date',
                        'end_date',
                        'courses.name',
                        DB::raw('CONCAT(first_name, " ", last_name) AS instructor'))
                    ->get();
            case(5):
            case(2):
                return DB::table('courses')
                    ->select('courses.*', DB::raw("beta_courses.id IS NOT NULL AS is_beta_course"))
                    ->leftJoin('beta_courses', 'courses.id', '=', 'beta_courses.id')
                    ->where('user_id', $user->id)
                    ->orderBy('order')
                    ->get();
            case(4):
                $sections = DB::table('graders')
                    ->join('sections', 'section_id', '=', 'sections.id')
                    ->where('user_id', $user->id)
                    ->get()
                    ->pluck('section_id');

                $course_section_info = DB::table('courses')
                    ->join('sections', 'courses.id', '=', 'sections.course_id')
                    ->select('courses.id AS id',
                        DB::raw('courses.id AS course_id'),
                        'start_date',
                        'end_date',
                        DB::raw('courses.name AS course_name'),
                        DB::raw('sections.name AS section_name')
                    )
                    ->whereIn('sections.id', $sections)->orderBy('start_date', 'desc')
                    ->get();

                $course_sections = [];
                foreach ($course_section_info as $course_section) {
                    if (!isset($course_sections[$course_section->course_id])) {
                        $course_sections[$course_section->course_id]['id'] = $course_section->course_id;
                        $course_sections[$course_section->course_id]['name'] = $course_section->course_name;
                        $course_sections[$course_section->course_id]['start_date'] = $course_section->start_date;
                        $course_sections[$course_section->course_id]['end_date'] = $course_section->end_date;
                        $course_sections[$course_section->course_id]['sections'] = [];
                    }
                    $course_sections[$course_section->course_id]['sections'][] = $course_section->section_name;
                }

                foreach ($course_sections as $key => $course_section) {
                    $course_sections[$key]['sections'] = implode(', ', $course_section['sections']);
                }
                $course_sections = array_values($course_sections);
                return collect($course_sections);

        }
    }

    /**
     * @param StoreCourse $request
     * @param Course $course
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @param Section $section
     * @param School $school
     * @return array
     * @throws Exception
     */

    public
    function store(StoreCourse $request,
                   Course      $course,
                   Enrollment  $enrollment,
                   FinalGrade  $finalGrade,
                   Section     $section,
                   School      $school): array
    {
        //todo: check the validation rules
        $response['type'] = 'error';
        $authorized = Gate::inspect('create', $course);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        $is_instructor = $request->user()->role === 2;
        $whitelisted_domains = [];
        try {
            $data = $request->validated();
            DB::beginTransaction();
            if (!$is_instructor) {
                $data['start_date'] = date("Y-m-d");
                $datetime = new DateTime('+3 months');
                $data['end_date'] = $datetime->format("Y-m-d");
                $data['crn'] = 'N/A';
                $data['section'] = 'N/A';
                $data['term'] = 'N/A';
                $data['alpha'] = 0;
                $data['anonymous_users'] = 0;
            }
            $data['user_id'] = $request->user()->id;
            $data['school_id'] = $is_instructor ? $this->getSchoolIdFromRequest($request, $school) : 1;
            $formative = isset($data['formative']) && $data['formative'];
            if ($formative) {
                $data['start_date'] = $data['end_date'] = date('Y-m-d', time());
                $data['lms'] = 0;
                $data['alpha'] = 0;
            } else {
                if ($is_instructor) {
                    $whitelisted_domains = $data['whitelisted_domains'];
                    unset($data['whitelisted_domains']);
                }
            }

            $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'] . '00:00:00', auth()->user()->time_zone);
            $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'] . '00:00:00', auth()->user()->time_zone);

            $data['shown'] = 0;
            $data['public_description'] = $request->public_description;
            $data['private_description'] = $request->private_description;
            $data['order'] = 0;
            //create the main section
            $section->name = $formative ? 'Default' : $data['section'];
            $section->crn = $formative ? '' : $data['crn'];
            unset($data['section']);
            unset($data['crn']);
            unset($data['school']);
            //create the course


            $new_course = $course->create($data);

            $section->course_id = $new_course->id;
            $section->save();
            if ($is_instructor && !$formative) {
                foreach ($whitelisted_domains as $whitelisted_domain) {
                    $whiteListedDomain = new WhitelistedDomain();
                    $whiteListedDomain->course_id = $new_course->id;
                    $whiteListedDomain->whitelisted_domain = $whitelisted_domain;
                    $whiteListedDomain->save();
                }
            }

            $course->enrollFakeStudent($new_course->id, $section->id, $enrollment);
            $finalGrade->setDefaultLetterGrades($new_course->id);

            $this->reOrderAllCourses();

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$request->name</strong> has been created.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>$request->name</strong>.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public
    function getSchoolIdFromRequest(Request $request, School $school)
    {

        return $request->school
            ? $school->where('name', $request->school)->first()->id
            : $school->first()->id;
    }

    /**
     *
     * Update the specified resource in storage.
     *
     *
     * @param StoreCourse $request
     * @param Course $course
     * @param School $school
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception
     */
    public
    function update(StoreCourse $request,
                    Course      $course,
                    School      $school,
                    BetaCourse  $betaCourse): array
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('update', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $is_beta_course = $betaCourse->where('id', $course->id)->first();
        if ($message = $this->failsTetherCourseValidation($request, $course, $is_beta_course)) {
            $response['message'] = $message;
            return $response;
        }
        try {
            $data = $request->validated();
            DB::beginTransaction();

            $data['public_description'] = $request->public_description;
            $data['private_description'] = $request->private_description;
            if ($request->user()->role === 2) {
                $data['school_id'] = $this->getSchoolIdFromRequest($request, $school);
                if (!$request->formative) {
                    $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'], $request->user()->time_zone);
                    $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'], $request->user()->time_zone);
                    $whitelisted_domains = $data['whitelisted_domains'];
                    unset($data['whitelisted_domains']);
                    DB::table('whitelisted_domains')->where('course_id', $course->id)->delete();
                    foreach ($whitelisted_domains as $whitelisted_domain) {
                        $whitelistedDomain = new WhitelistedDomain();
                        $whitelistedDomain->course_id = $course->id;
                        $whitelistedDomain->whitelisted_domain = $whitelisted_domain;
                        $whitelistedDomain->save();
                    }
                }
                $data['textbook_url'] = $request->textbook_url;
                if ($is_beta_course && $request->untether_beta_course) {
                    $betaCourse->untether($course);
                }
                unset($data['school']);
            }
            $course->update($data);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been updated.";
            $response['is_beta_course'] = $betaCourse->where('id', $course->id)->first();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param $request
     * @param $course
     * @param $is_beta_course
     * @return string
     */
    public
    function failsTetherCourseValidation($request, $course, $is_beta_course): string
    {
        $message = '';
        $at_least_one_beta_course_exists = BetaCourse::where('alpha_course_id', $course->id)->first();
        if ($course->alpha && (int)$request->alpha === 0 && $at_least_one_beta_course_exists) {
            $message = "You are trying to change an Alpha course into a non-Alpha course but Beta courses are currently tethered to this course.";
        }
        if ((int)$request->alpha === 1 && $is_beta_course) {
            $message = "You can't change a Beta course into an Alpha course.";
        }
        return $message;
    }

    /**
     *
     * Delete a course
     *
     * @param DestroyCourse $request
     * @param Course $course
     * @param AssignToTiming $assignToTiming
     * @param BetaAssignment $betaAssignment
     * @param BetaCourse $betaCourse
     * @param BetaCourseApproval $betaCourseApproval
     * @return array
     * @throws Exception
     */

    public
    function destroy(DestroyCourse      $request,
                     Course             $course,
                     AssignToTiming     $assignToTiming,
                     BetaAssignment     $betaAssignment,
                     BetaCourse         $betaCourse,
                     BetaCourseApproval $betaCourseApproval): array

    {


        $response['type'] = 'error';

        $authorized = Gate::inspect('delete', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $request->validated();
        if (BetaCourse::where('alpha_course_id', $course->id)->first()) {
            $response['message'] = "You cannot delete an Alpha course with tethered Beta courses.";
            return $response;
        }

        try {
            DB::beginTransaction();
            foreach ($course->assignments as $assignment) {
                $assignment_question_ids = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->get()
                    ->pluck('id');

                DB::table('assignment_question_learning_tree')
                    ->whereIn('assignment_question_id', $assignment_question_ids)
                    ->delete();
                $assignToTiming->deleteTimingsGroupsUsers($assignment);
                $assignment->questions()->detach();
                $assignment->submissions()->delete();
                $assignment->fileSubmissions()->delete();
                $assignment->scores()->delete();
                $assignment->canGiveUps()->delete();
                $assignment->cutups()->delete();
                $assignment->seeds()->delete();
                DB::table('randomized_assignment_questions')
                    ->where('assignment_id', $assignment->id)
                    ->delete();
                $assignment->graders()->detach();
                $betaAssignment->where('id', $assignment->id)->delete();

                DB::table('question_level_overrides')->where('assignment_id', $assignment->id)->delete();
                DB::table('compiled_pdf_overrides')->where('assignment_id', $assignment->id)->delete();
                DB::table('assignment_level_overrides')->where('assignment_id', $assignment->id)->delete();

                DB::table('learning_tree_time_lefts')->where('assignment_id', $assignment->id)->delete();
                DB::table('learning_tree_successful_branches')->where('assignment_id', $assignment->id)->delete();
                DB::table('remediation_submissions')->where('assignment_id', $assignment->id)->delete();
                DB::table('assignment_question_time_on_tasks')->where('assignment_id', $assignment->id)->delete();
                DB::table('review_histories')->where('assignment_id', $assignment->id)->delete();
                DB::table('shown_hints')->where('assignment_id', $assignment->id)->delete();
                DB::table('submission_confirmations')->where('assignment_id', $assignment->id)->delete();
                $betaCourseApproval->where('beta_assignment_id', $assignment->id)->delete();
                DeleteAssignmentDirectoryFromS3::dispatch($assignment->id);
            }
            DB::table('grader_notifications')
                ->where('course_id', $course->id)
                ->delete();
            $course->extensions()->delete();
            $course->assignments()->delete();


            AssignmentGroupWeight::where('course_id', $course->id)->delete();
            AssignmentGroup::where('course_id', $course->id)->where('user_id', Auth::user()->id)->delete();//get rid of the custom assignment groups
            $course->enrollments()->delete();
            foreach ($course->sections as $section) {
                $section->graders()->delete();
                $section->delete();
            }

            $course->finalGrades()->delete();
            $betaCourse->where('id', $course->id)->delete();
            DB::table('analytics_dashboards')->where('course_id', $course->id)->delete();
            DB::table('contact_grader_overrides')->where('course_id', $course->id)->delete();
            DB::table('whitelisted_domains')->where('course_id', $course->id)->delete();
            $course->delete();
            DB::commit();
            $response['type'] = 'info';
            $response['message'] = "The course <strong>$course->name</strong> has been deleted.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Course $course
     * @param string $operator_text
     * @param int $num_days
     * @return array
     * @throws Exception
     */
    public
    function getCoursesToReset(Course $course, string $operator_text, int $num_days): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getConcludedCourses', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            $response['courses_to_reset'] = $course->concludedCourses($operator_text, $num_days);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the hundred day courses.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Section $section
     * @param Course $new_course
     * @param Course $course
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @return void
     */
    public
    function prepareNewCourse(Section    $section,
                              Course     $new_course,
                              Course     $course,
                              Enrollment $enrollment,
                              FinalGrade $finalGrade)
    {
        $section->name = 'Main';
        $section->course_id = $new_course->id;
        $section->crn = "To be determined";
        $section->save();
        $course->enrollFakeStudent($new_course->id, $section->id, $enrollment);
        $finalGrade->setDefaultLetterGrades($new_course->id);
        $this->reorderAllCourses();

    }

    /**
     * @param AssignmentGroup $assignmentGroup
     * @param Course $cloned_course
     * @param $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param Course $course
     * @return mixed
     */
    public
    function cloneAssignment(AssignmentGroup $assignmentGroup, Course $cloned_course, $assignment, AssignmentGroupWeight $assignmentGroupWeight, Course $course)
    {
        $cloned_assignment_group_id = $assignmentGroup->importAssignmentGroupToCourse($cloned_course, $assignment);
        $assignmentGroupWeight->importAssignmentGroupWeightToCourse($course, $cloned_course, $cloned_assignment_group_id, false);
        $cloned_assignment = $assignment->replicate();
        $cloned_assignment->course_id = $cloned_course->id;
        $cloned_assignment->shown = 0;
        if ($cloned_assignment->assessment_type !== 'real time') {
            $cloned_assignment->solutions_released = 0;
        }
        if ($cloned_assignment->assessment_type === 'delayed') {
            $cloned_assignment->show_scores = 0;
        }
        $cloned_assignment->students_can_view_assignment_statistics = 0;
        $cloned_assignment->assignment_group_id = $cloned_assignment_group_id;
        $cloned_assignment->lms_resource_link_id = null;
        $cloned_assignment->save();
        return $cloned_assignment;
    }

}
