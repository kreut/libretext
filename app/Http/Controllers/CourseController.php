<?php

namespace App\Http\Controllers;

use App\AssignmentSyncQuestion;
use App\Course;
use App\FinalGrade;
use App\User;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\CourseAccessCode;
use App\Enrollment;
use App\Http\Requests\StoreCourse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatter;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;

class CourseController extends Controller
{

    use DateFormatter;

    /**
     * @param Request $request
     * @param Course $course
     * @param AssignmentGroup $assignmentGroup
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Enrollment $enrollment
     * @param FinalGrade $finalGrade
     * @return array
     * @throws Exception
     */
    public function import(Request $request,
                           Course $course,
                           AssignmentGroup $assignmentGroup,
                           AssignmentSyncQuestion $assignmentSyncQuestion,
                           Enrollment $enrollment,
                           FinalGrade $finalGrade)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('import', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $imported_course = $course->replicate();
            $imported_course->name = "$imported_course->name Import";
            $imported_course->shown = 0;
            $imported_course->user_id = $request->user()->id;
            $imported_course->save();
            foreach ($course->assignments as $assignment) {
                $imported_assignment_group_id = $assignmentGroup->importAssignmentGroupToCourse($imported_course, $assignment);
                $imported_assignment = $assignment->replicate();
                $imported_assignment->course_id = $imported_course->id;
                $imported_assignment->shown = 0;
                if ($imported_assignment->assessment_type !== 'real time') {
                    $imported_assignment->solutions_released = 0;
                }
                if ($imported_assignment->assessment_type === 'delayed') {
                    $imported_assignment->show_scores = 0;
                }
                $imported_assignment->students_can_view_assignment_statistics = 0;
                $imported_assignment->assignment_group_id = $imported_assignment_group_id;
                $imported_assignment->save();
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $imported_assignment->id);
            }
            $course->enrollFakeStudent($imported_course->id, $enrollment);
            $finalGrade->setDefaultLetterGrades($imported_course->id);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "<strong>$imported_course->name</strong> has been imported.  </br></br>Don't forget to change the dates associated with this course and all of its assignments.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getImportable(Request $request, Course $course)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('getImportable', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $importable_courses = DB::table('courses')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('public', 1)
                ->orWhere('user_id', $request->user()->id)
                ->select('name', 'first_name', 'last_name', 'courses.id')
                ->get();
            $formatted_importable_courses = [];
            foreach ($importable_courses as $course) {
                $course_info = "$course->name --- $course->first_name $course->last_name";
                if (!in_array($course_info, $formatted_importable_courses)) {
                    $formatted_importable_courses[] = [
                        'course_id' => $course->id,
                        'formatted_course' => "$course->name --- $course->first_name $course->last_name"
                    ];
                }
            }
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
    public function index(Request $request, Course $course)
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

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your courses.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function updateStudentsCanViewWeightedAverage(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateStudentsCanViewWeightedAverage', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignmentGroupWeight->validateCourseWeights($course);
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

    public function show(Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['course'] = ['name' => $course->name,
                'students_can_view_weighted_average' => $course->students_can_view_weighted_average,
                'letter_grades_released' => $course->finalGrades->letter_grades_released,
                'graders' => $course->graderNamesAndIds,
                'access_code' => $course->accessCodes->access_code ?? false,
                'start_date' => $course->start_date,
                'end_date' => $course->end_date,
                'public' => $course->public];

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param Course $course
     * @param CourseAccessCode $courseAccessCode
     * @param int $shown
     * @return array
     * @throws Exception
     */
    public function showCourse(Request $request, Course $course, CourseAccessCode $courseAccessCode, int $shown)
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
            $access_code_message = 'In addition, the course access code has been ';
            if (!$shown) {
                $course_access_code = $courseAccessCode->refreshCourseAccessCode($course->id);
                $access_code_message .= 'refreshed.';
            } else {
                $courseAccessCode->where(['course_id' => $course->id])->delete();
                $course_access_code = 'None';
                $access_code_message .= 'revoked.';
            }

            DB::commit();
            $response['type'] = !$shown ? 'success' : 'info';
            $shown_message = !$shown ? 'can' : 'cannot';
            $response['course_access_code'] = $course_access_code;
            $response['message'] = "Your students <strong>{$shown_message}</strong> view this course.  $access_code_message";

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
     * @return \Illuminate\Support\Collection
     */
    public function getCourses($user)
    {

        switch ($user->role) {
            case(2):
                return DB::table('courses')
                    ->leftJoin('course_access_codes', 'courses.id', '=', 'course_access_codes.course_id')
                    ->select('courses.*', 'course_access_codes.access_code')
                    ->where('user_id', $user->id)->orderBy('start_date', 'desc')
                    ->get();
            case(4):
                $courses = DB::table('graders')
                    ->where('user_id', $user->id)
                    ->get()
                    ->pluck('course_id');
                return DB::table('courses')
                    ->leftJoin('course_access_codes', 'courses.id', '=', 'course_access_codes.course_id')
                    ->select('courses.*', 'course_access_codes.access_code')
                    ->whereIn('courses.id', $courses)->orderBy('start_date', 'desc')
                    ->get();
        }
    }

    /**
     *
     * Store a newly created resource in storage.
     *
     * @param StoreCourse $request
     * @param Course $course
     * @param CourseAccessCode $course_access_code
     * @return mixed
     * @throws Exception
     */

    public function store(StoreCourse $request, Course $course, CourseAccessCode $course_access_code, Enrollment $enrollment, FinalGrade $finalGrade)
    {
        //todo: check the validation rules
        $response['type'] = 'error';
        $authorized = Gate::inspect('create', $course);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::beginTransaction();
            $data = $request->validated();
            $data['user_id'] = auth()->user()->id;


            $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'] . '00:00:00', auth()->user()->time_zone);
            $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'] . '00:00:00', auth()->user()->time_zone);
            $data['shown'] = 0;
            //create the course
            $new_course = $course->create($data);
            $course->enrollFakeStudent($new_course->id, $enrollment);
            $finalGrade->setDefaultLetterGrades($new_course->id);

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

    /**
     *
     * Update the specified resource in storage.
     *
     *
     * @param StoreCourse $request
     * @param Course $course
     * @return mixed
     * @throws Exception
     */
    public function update(StoreCourse $request, Course $course)
    {
        $response['type'] = 'error';

        $authorized = Gate::inspect('update', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $data = $request->validated();
            $data['start_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['start_date'], auth()->user()->time_zone);
            $data['end_date'] = $this->convertLocalMysqlFormattedDateToUTC($data['end_date'], auth()->user()->time_zone);

            $course->update($data);
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete a course
     *
     * @param Course $course
     * @param CourseAccessCode $course_access_code
     * @param Enrollment $enrollment
     * @return mixed
     * @throws Exception
     */
    public function destroy(Course $course)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('delete', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $course->accessCodes()->delete();
            foreach ($course->assignments as $assignment) {
                $assignment_question_ids = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                    ->get()
                    ->pluck('id');

                DB::table('assignment_question_learning_tree')
                    ->whereIn('assignment_question_id', $assignment_question_ids)
                    ->delete();

                $assignment->questions()->detach();
                $assignment->scores()->delete();
                $assignment->seeds()->delete();

            }
            $course->assignments()->delete();

            AssignmentGroupWeight::where('course_id', $course->id)->delete();
            AssignmentGroup::where('course_id', $course->id)->where('user_id', Auth::user()->id)->delete();//get rid of the custom assignment groups
            $course->enrollments()->delete();
            $course->graders()->delete();
            $course->finalGrades()->delete();
            $course->delete();
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The course <strong>$course->name</strong> has been deleted.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$course->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
