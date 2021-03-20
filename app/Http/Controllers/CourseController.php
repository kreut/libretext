<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignToGroup;
use App\AssignToTiming;
use App\Course;
use App\FinalGrade;
use App\Http\Requests\UpdateCourse;
use App\Section;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Enrollment;
use App\Http\Requests\StoreCourse;
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


    public function getCoursesAndAssignments(Request $request)
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
                    DB::raw('assignments.id AS assignment_id'),
                    DB::raw('assignments.name AS assignment_name'))
                ->orderBy('courses.start_date', 'desc')
                ->get();
            $course_ids = [];
            foreach ($results as $key => $value) {
                $course_id = $value->course_id;
                if (!in_array($course_id, $course_ids)) {
                    $courses[] = ['value' => $course_id, 'text' => $value->course_name];
                    $course_ids[] = $course_id;
                }
                $assignments[$course_id][] = ['value' => $value->assignment_id, 'text' => $value->assignment_name];
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
     * @param AssignToTiming $assignToTiming
     * @param AssignToGroup $assignToGroup
     * @return array
     * @throws Exception '
     */
    public function import(Request $request,
                           Course $course,
                           AssignmentGroup $assignmentGroup,
                           AssignmentGroupWeight $assignmentGroupWeight,
                           AssignmentSyncQuestion $assignmentSyncQuestion,
                           Enrollment $enrollment,
                           FinalGrade $finalGrade,
                           Section $section)
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
            $imported_course->show_z_scores = 0;
            $imported_course->students_can_view_weighted_average = 0;
            $imported_course->user_id = $request->user()->id;
            $imported_course->save();
            foreach ($course->assignments as $assignment) {
                $imported_assignment_group_id = $assignmentGroup->importAssignmentGroupToCourse($imported_course, $assignment);
                $assignmentGroupWeight->importAssignmentGroupWeightToCourse($course, $imported_course, $imported_assignment_group_id, false);
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
                $assignment->saveAssignmentTimingAndGroup($imported_assignment);
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $imported_assignment->id);
            }

            $section->name = 'Main';
            $section->course_id = $imported_course->id;
            $section->save();
            $course->enrollFakeStudent($imported_course->id, $section->id, $enrollment);
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

    public function updateShowZScores(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
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

    public function updateStudentsCanViewWeightedAverage(Request $request, Course $course, AssignmentGroupWeight $assignmentGroupWeight)
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
                'sections' => $course->sections,
                'show_z_scores' => $course->show_z_scores,
                'graders' => $course->graderInfo(),
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
     * @param int $shown
     * @return array
     * @throws Exception
     */
    public function showCourse(Request $request, Course $course, int $shown)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showCourse', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $course->sections()->update(['access_code' => null]);
            $course->shown = !$shown;
            $course->save();

            $response['type'] = !$shown ? 'success' : 'info';
            $shown_message = !$shown ? 'can' : 'cannot';
            $access_code_message = !$shown ? '' : '  In addition, all course access codes have been revoked.';

            $response['message'] = "Your students <strong>{$shown_message}</strong> view this course.{$access_code_message}";
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
    public function getCourses($user)
    {

        switch ($user->role) {
            case(2):
                return DB::table('courses')
                    ->select('*')
                    ->where('user_id', $user->id)->orderBy('start_date', 'desc')
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
     * @return array
     * @throws Exception
     */

    public function store(StoreCourse $request,
                          Course $course,
                          Enrollment $enrollment,
                          FinalGrade $finalGrade,
                          Section $section)
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
            //create the main section
            $section->name = $data['section'];
            $section->course_id = $new_course->id;
            $section->save();
            $course->enrollFakeStudent($new_course->id, $section->id, $enrollment);
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
    public function update(UpdateCourse $request, Course $course)
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
     * @param AssignToTiming $assignToTiming
     * @return mixed
     * @throws Exception
     */
    public function destroy(Course $course, AssignToTiming $assignToTiming)
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('delete', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            foreach ($course->assignments as $assignment) {
                $assignment_question_ids = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                    ->get()
                    ->pluck('id');

                DB::table('assignment_question_learning_tree')
                    ->whereIn('assignment_question_id', $assignment_question_ids)
                    ->delete();
                $assignToTiming->deleteTimingsGroupsUsers($assignment);
                $assignment->questions()->detach();
                $assignment->scores()->delete();
                $assignment->seeds()->delete();

            }
            $course->assignments()->delete();

            AssignmentGroupWeight::where('course_id', $course->id)->delete();
            AssignmentGroup::where('course_id', $course->id)->where('user_id', Auth::user()->id)->delete();//get rid of the custom assignment groups
            $course->enrollments()->delete();
            foreach ($course->sections as $section) {
                $section->graders()->delete();
                $section->delete();
            }

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
