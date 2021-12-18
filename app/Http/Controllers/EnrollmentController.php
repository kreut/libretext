<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignToGroup;
use App\AssignToUser;
use App\Enrollment;
use App\Course;

use App\Extension;
use App\ExtraCredit;
use App\Http\Requests\DestroyEnrollment;
use App\Http\Requests\UpdateEnrollment;
use App\LtiGradePassback;
use App\Score;
use App\Section;
use App\Seed;
use App\Submission;
use App\SubmissionFile;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreEnrollment;
use App\Exceptions\Handler;
use \Exception;

use App\Traits\DateFormatter;
use Illuminate\Support\Facades\Storage;

class EnrollmentController extends Controller
{

    use DateFormatter;

    /**
     * @param UpdateEnrollment $request
     * @param Course $course
     * @param User $user
     * @param Enrollment $enrollment
     * @param Section $section
     * @param AssignToUser $assignToUser
     * @return array
     * @throws Exception
     */
    public function update(UpdateEnrollment $request,
                           Course           $course,
                           User             $user,
                           Enrollment       $enrollment,
                           Section          $section,
                           AssignToUser     $assignToUser): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('update', [$enrollment, $course, $user]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $data = $request->validated();

        if (!app()->environment('testing')) {
            $date = Carbon::now()->format('Y-m-d');
            $log_file = "logs/laravel-$date.log";
            $contents = "Moving student:" . $user->id . " to " . print_r($request->all(), true);
            Storage::disk('s3')->put("$log_file", $contents, ['StorageClass' => 'STANDARD_IA']);
        }
        $new_section_id = $data['section_id'];
        $section_name = $section->find($new_section_id)->name;
        try {
            $current_section_id = $enrollment->where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->first()
                ->section_id;
            $current_section_fake_student_id = $enrollment->firstNonFakeStudent($current_section_id);

            $new_section_fake_student_id = $enrollment->firstNonFakeStudent($new_section_id);
            $current_assignments_info = User::find($current_section_fake_student_id)
                ->assignmentsAndAssignToTimingsByCourse($course->id);

            $assign_to_timings_to_remove_ids = [];
            foreach ($current_assignments_info as $value) {
                $assign_to_timings_to_remove_ids[] = $value['assign_to_timing_id'];
            }

            $new_assignments_info = User::find($new_section_fake_student_id)
                ->assignmentsAndAssignToTimingsByCourse($course->id);

            $new_assign_to_timing_ids = [];
            foreach ($new_assignments_info as $value) {
                $new_assign_to_timing_ids[] = $value['assign_to_timing_id'];
            }

            DB::beginTransaction();
            $assignToUser->where('user_id', $user->id)->whereIn('assign_to_timing_id', $assign_to_timings_to_remove_ids)->delete();
            $enrollment->where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->update(['section_id' => $new_section_id]);

            ///clean up database call
            foreach ($new_assign_to_timing_ids as $assign_to_timing_id) {
                $section_assign_to_groups = AssignToGroup::where('assign_to_timing_id', $assign_to_timing_id)
                    ->where('group', 'section')->get();
                $assigned_user_to_new_assign_to_group = false;
                foreach ($section_assign_to_groups as $section_assign_to_group) {
                    if (!$assigned_user_to_new_assign_to_group && $section_assign_to_group->group_id === $new_section_id) {
                        $assignToUser = new AssignToUser();
                        $assignToUser->user_id = $user->id;
                        $assignToUser->assign_to_timing_id = $assign_to_timing_id;
                        $assignToUser->save();
                        $assigned_user_to_new_assign_to_group = true;
                    }
                }
                if (!$assigned_user_to_new_assign_to_group) {
                    $course_assign_to_group = AssignToGroup::where('assign_to_timing_id', $assign_to_timing_id)
                        ->where('group', 'course')->first();
                    if ($course_assign_to_group) {
                        $assignToUser = new AssignToUser();
                        $assignToUser->user_id = $user->id;
                        $assignToUser->assign_to_timing_id = $assign_to_timing_id;
                        $assignToUser->save();
                    }
                }
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "We have moved <strong>{$user->first_name} {$user->last_name}</strong> to <strong>$section_name</strong>.";
        } catch
        (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were unable to move <strong>{$user->first_name} {$user->last_name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param DestroyEnrollment $request
     * @param Section $section
     * @param User $user
     * @param Assignment $assignment
     * @param Enrollment $enrollment
     * @param Submission $submission
     * @param SubmissionFile $submissionFile
     * @param Score $score
     * @param AssignToUser $assignToUser
     * @param Extension $extension
     * @param ExtraCredit $extraCredit
     * @param LtiGradePassback $ltiGradePassback
     * @param Seed $seed
     * @return array
     * @throws Exception
     */
    public
    function destroy(DestroyEnrollment $request,
                     Section           $section,
                     User              $user,
                     Assignment        $assignment,
                     Enrollment        $enrollment,
                     Submission        $submission,
                     SubmissionFile    $submissionFile,
                     Score             $score,
                     AssignToUser      $assignToUser,
                     Extension         $extension,
                     ExtraCredit       $extraCredit,
                     LtiGradePassback  $ltiGradePassback,
                     Seed              $seed
    )
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('destroy', [$enrollment, $section, $user]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $request->validated();
        $course_id = $section->course->id;

        $student_name = $user->first_name . ' ' . $user->last_name;
        $assignments_to_remove_ids = [];
        $assign_to_timings_to_remove_ids = [];
        $assignment_timings_and_assignment_info = $assignToUser->assignToTimingsAndAssignmentsByAssignmentIdByCourse($course_id);
        foreach ($assignment_timings_and_assignment_info as $value) {
            $assignments_to_remove_ids[] = $value->assignment_id;
            $assign_to_timings_to_remove_ids[] = $value->assign_to_timing_id;
        }
        try {
            DB::beginTransaction();

            $assignment->removeUserInfo($user,
                $assignments_to_remove_ids,
                $assign_to_timings_to_remove_ids,
                $submission,
                $submissionFile,
                $score,
                $assignToUser,
                $extension,
                $ltiGradePassback,
                $seed);

            $extraCredit->where('user_id', $user->id)->where('course_id', $course_id)->delete();
            $enrollment->where('user_id', $user->id)->where('section_id', $section->id)->delete();

            if ($this->_assignTosWereNotRemoved($user->id, $course_id)) {
                throw new Exception("User: $user->id from Course: $course_id did not have all assign tos removed.");
            }

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "We have unenrolled <strong>$student_name</strong> from the course.";
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to unenroll <strong>$student_name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param $user_id
     * @param $course_id
     * @return false|Model|Builder|object|null
     */
    private function _assignTosWereNotRemoved($user_id, $course_id)
    {
        /**select *
         * FROM assign_to_timings
         * JOIN assign_to_users
         * ON (assign_to_timings.id = assign_to_users.assign_to_timing_id)
         * WHERE assignment_id IN (SELECT id from assignments where course_id=220)
         * AND user_id = 153
         ***/
        $assignments = DB::table('assignments')->where('course_id', $course_id)->get('id');
        $assign_tos_exist = false;
        if ($assignments) {
            $assignment_ids = $assignments->pluck('id')->toArray();
            $assign_tos_exist = DB::table('assign_to_timings')
                ->join('assign_to_users', 'assign_to_timings.id', '=', 'assign_to_users.assign_to_timing_id')
                ->whereIn('assignment_id', $assignment_ids)
                ->where('user_id', $user_id)
                ->first();
        }
        return $assign_tos_exist;

    }

    public function enrollmentsFromAssignment(Assignment $assignment, Enrollment $enrollment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('enrollmentsFromAssignment', [$enrollment, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $enrollments_info = DB::table('enrollments')
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('sections.course_id', $assignment->course->id)
                ->where('users.fake_student', 0)
                ->select('users.id AS user_id',
                    DB::raw('CONCAT(first_name, " " , last_name) AS name'))
                ->orderBy('first_name')
                ->get();
            $enrollments[0] = ['text' => 'Select a student', 'value' => null];
            $enrollments[] = ['text' => 'Everybody', 'value' => -1];
            foreach ($enrollments_info as $info) {
                $enrollments[] = ['text' => $info->name, 'value' => $info->user_id];
            }
            $response['enrollments'] = $enrollments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrollments.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public
    function details(Request $request, Course $course, Enrollment $enrollment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('details', [$enrollment, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $enrollments_info = DB::table('enrollments')
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->join('users', 'enrollments.user_id', '=', 'users.id')
                ->where('sections.course_id', $course->id)
                ->where('users.fake_student', 0)
                ->select('users.id',
                    DB::raw('CONCAT(first_name, " " , last_name) AS name'),
                    'email',
                    'sections.name AS section',
                    'student_id',
                    'sections.id AS section_id',
                    'enrollments.created_at AS enrollment_date')
                ->orderBy('first_name')
                ->get();
            $sections = [];
            foreach ($course->sections as $section) {
                $sections[] = ['text' => $section->name, 'value' => $section->id];
            }
            $enrollments = [];
            foreach ($enrollments_info as $enrollment) {
                $enrollment->enrollment_date = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($enrollment->enrollment_date, $request->user()->time_zone, 'F d, Y');
                $enrollments[] = $enrollment;
            }
            $response['sections'] = $sections;
            $response['enrollments'] = $enrollments;
            $response['lms'] = $course->lms;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrollments.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    public
    function index(Request $request, Enrollment $enrollment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $response['enrollments'] = $enrollment->index();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrollments.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    /**
     * @param StoreEnrollment $request
     * @param Enrollment $enrollment
     * @param Section $Section
     * @param AssignToUser $assignToUser
     * @return array|Application|ResponseFactory|Response|string
     * @throws Exception
     */
    public
    function store(StoreEnrollment $request,
                   Enrollment      $enrollment,
                   Section         $Section,
                   AssignToUser    $assignToUser)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $data = $request->validated();
            if ($request->is_lms) {
                $user = request()->user();
                $user->time_zone = $data['time_zone'];
                $user->student_id = $data['student_id'];
                $user->save();
            }
            $section = $Section->where('access_code', '=', $data['access_code'])
                ->where('access_code', '<>', null)
                ->first();
            if (!$section) {
                //not sure I even need this but I'm being extra cautious
                $response = '{"message":"The given data was invalid.","errors":{"access_code":["The selected access code is invalid."]}}';
                return response($response, 422);
            }
            if ($section->course->enrollments->isNotEmpty()) {
                $enrolled_user_ids = $section->course->enrollments->pluck('user_id')->toArray();
                if (in_array($request->user()->id, $enrolled_user_ids)) {
                    $response['message'] = 'You are already enrolled in another section of this course.';
                    return $response;
                }
                $student_id_exists = DB::table('users')
                    ->whereIn('id', $enrolled_user_ids)
                    ->where('student_id', $request->user()->student_id)
                    ->where('last_name', $request->user()->last_name)
                    ->first();
                if ($student_id_exists) {
                    $response['message'] = 'Someone with your student ID and the same last name is already enrolled in this course.';
                    return $response;
                }
            }
            $course_id = $section->course_id;
            $section_id = $section->id;

            //make sure they don't sign up twice!
            $response['validated'] = true;
            $course_section_name = "{$section->course->name} - {$section->name}";

            if (Enrollment::where('user_id', $request->user()->id)->where('section_id', $section_id)->get()->isNotEmpty()) {
                $response['type'] = 'error';
                $response['message'] = "You are already enrolled in <strong>$course_section_name</strong>.";
            } else {

                $enrollment->user_id = $request->user()->id;
                $enrollment->section_id = $section_id;
                $enrollment->course_id = $course_id;
                $enrollment->save();

                //add the assign tos
                $assignments = $section->course->assignments;
                $assignToUser->assignToUserForAssignments($assignments, $enrollment->user_id, $section->id);


                $response['type'] = 'success';
                DB::commit();
                $response['message'] = "You are now enrolled in <strong>$course_section_name</strong>.";
            }
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error enrolling you in the course.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}

