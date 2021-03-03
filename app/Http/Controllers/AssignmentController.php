<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\Traits\DateFormatter;
use App\Course;
use App\Solution;
use App\Score;
use App\Extension;
use App\Submission;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreAssignment;
use Carbon\Carbon;

use \Illuminate\Http\Request;

use App\Exceptions\Handler;
use \Exception;

class AssignmentController extends Controller
{
    use DateFormatter;

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function validateAssessmentType(Request $request, Assignment $assignment)
    {
        $response['type'] = 'error';
        try {
            $learning_tree = DB::table('assignment_question_learning_tree')
                ->join('assignment_question', 'assignment_question_id', '=', 'assignment_question.id')
                ->where('assignment_id', $assignment->id)
                ->select('question_id')
                ->first();
            $open_ended = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('open_ended_submission_type', '<>', '0')
                ->select('assignment_id')
                ->first();
            $question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->select('assignment_id')
                ->first();
            $assessment_type = $request->assessment_type;
            $source = $request->source;

            if ($source === 'x' && ($question || $learning_tree)) {
                $response['message'] = "You can't switch to an external assignment until you remove all Adapt questions from the assignment.";
                return $response;
            }

            if (in_array($assessment_type, ['real time', 'clicker']) && $open_ended !== null) {
                $response['message'] = "You can't switch to a $assessment_type assessment type until you remove the open-ended questions from the assignment.";
                return $response;
            }
            if (in_array($assessment_type, ['delayed', 'real time', 'clicker']) && $learning_tree !== null) {
                $response['message'] = "You can't switch to a $assessment_type assessment type since this is not a learning tree assignment.";
                return $response;
            }
            if ($assessment_type === 'learning tree' && $question !== null) {
                $response['message'] = "You can't switch to a learning tree assessment type since this is not a learning tree assignment and you already have non-learning tree questions.";
                return $response;
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment question information.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function order(Request $request, Course $course, Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('order', [$assignment, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            DB::beginTransaction();
            $assignment->orderAssignments($request->ordered_assignments, $course);
            DB::commit();
            $response['message'] = 'Your assignments have been re-ordered.';
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error ordering the assignments for this course.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Course $course
     * @param Assignment $assignment
     * @param AssignmentGroup $assignmentGroup
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */
    public function importAssignment(Request $request,
                                     Course $course, Assignment $assignment,
                                     AssignmentGroup $assignmentGroup,
                                     AssignmentSyncQuestion $assignmentSyncQuestion,
                                     AssignmentGroupWeight $assignmentGroupWeight)
    {

        $response['type'] = 'error';
        $course_assignment = $request->course_assignment;
        $level = $request->level;

        $authorized = Gate::inspect('importAssignment', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        $assignment_id = $assignment->idByCourseAssignmentUser($course_assignment);
        if (!$assignment_id) {
            $response['message'] = "That is not an assignment from one of your courses.";
            return $response;
        }

        if (!in_array($level, ['properties_and_questions', 'properties_and_not_questions'])) {
            $response['message'] = "You should either choose 'properties and questions' or 'properties and not questions'.";
            return $response;

        }

        try {

            $assignment_id = $assignment->idByCourseAssignmentUser($course_assignment);
            $assignment = Assignment::find($assignment_id);

            DB::beginTransaction();

            $imported_assignment_group_id = $assignmentGroup->importAssignmentGroupToCourse($course, $assignment);
            $assignmentGroupWeight->importAssignmentGroupWeightToCourse($assignment->course, $course, $imported_assignment_group_id, true);
            $imported_assignment = $assignment->replicate();
            $imported_assignment->name = "$imported_assignment->name Import";
            $imported_assignment->course_id = $course->id;
            $imported_assignment->assignment_group_id = $imported_assignment_group_id;
            $imported_assignment->save();

            if ($level === 'properties_and_questions') {
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment_id, $imported_assignment->id);
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = ($level === 'properties_and_questions')
                ? "<strong>$imported_assignment->name</strong> and its questions have been imported.</br></br>Don't forget to change the dates associated with this assignment."
                : "<strong>$imported_assignment->name</strong> has been imported without its questions.</br></br>Don't forget to change the dates associated with this assignment.";

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function getImportableAssignmentsByUser(Request $request, Course $course)
    {

        $response['type'] = 'error';

        try {

            $assignments_by_user = DB::table('assignments')
                ->join('courses', 'assignments.course_id', '=', 'courses.id')
                ->select(DB::raw('assignments.id AS assignment_id, courses.name AS course_name, assignments.name AS assignment_name'))
                ->where('courses.user_id', $request->user()->id)
                ->where('courses.id', '<>', $course->id)//don't get them for this course
                ->get();
            $assignments = [];

            foreach ($assignments_by_user as $key => $value) {
                $assignments[] = "$value->course_name --- $value->assignment_name";
            }
            $response['all_assignments'] = $assignments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your assignments.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function createAssignmentFromTemplate(Request $request, Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('createFromTemplate', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignments = Assignment::find($assignment->id);
            $new_assignment = $assignments->replicate();
            $new_assignment->name = $new_assignment->name . " copy";
            $new_assignment->save();
            $response['message'] = "<strong>$new_assignment->name</strong> is using the same template as <strong>$assignment->name</strong>. Don't forget to add questions and update the assignment's dates.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating an assignment from {$assignment->name}.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function solutionsReleased(Request $request, Assignment $assignment, int $solutionsReleased)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('releaseSolutions', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['solutions_released' => !$solutionsReleased]);
            $solutions_released = !$solutionsReleased ? 'released' : 'hidden';
            $response['type'] = !$solutionsReleased ? 'success' : 'info';

            $response['message'] = "The solutions have been <strong>{$solutions_released}</strong>.  ";
            if (!$solutionsReleased) {
                $response['message'] .= $this->getActiveExtensionMessage($assignment, 'solution');
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function showAssignment(Request $request, Assignment $assignment, int $shown)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showAssignment', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['shown' => !$shown]);
            $response['type'] = !$shown ? 'success' : 'info';
            $shown = !$shown ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$shown}</strong> see this assignment.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating whether your students can see <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function showScores(Request $request, Assignment $assignment, int $showScores)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showScores', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['show_scores' => !$showScores]);
            $response['type'] = !$showScores ? 'success' : 'info';
            $scores_released = !$showScores ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$scores_released}</strong> view their scores.  ";
            if (!$showScores) {
                $response['message'] .= $this->getActiveExtensionMessage($assignment, 'score');
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param string $type
     * @return string
     */
    public function getActiveExtensionMessage(Assignment $assignment, string $type)
    {

        if ($assignment->extensions->isNotEmpty()) {
            foreach ($assignment->extensions as $key => $value) {
                if (time() < strtotime($value->extension)) {
                    $type_message = ($type === 'score') ? "other students' scores and grader comments" : "the solutions";
                    return "<br><br>Please note that at least one of your students has an active extension and they can potentially view {$type_message}.";
                }
            }
        }
        return '';
    }

    public function showPointsPerQuestion(Request $request, Assignment $assignment, int $showPointsPerQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showPointsPerQuestion', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['show_points_per_question' => !$showPointsPerQuestion]);
            $response['type'] = !$showPointsPerQuestion ? 'success' : 'info';
            $points_per_question = !$showPointsPerQuestion ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$points_per_question}</strong> view the points per question.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function showAssignmentStatistics(Request $request, Assignment $assignment, int $showAssignmentStatistics)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showAssignmentStatistics', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['students_can_view_assignment_statistics' => !$showAssignmentStatistics]);
            $response['type'] = !$showAssignmentStatistics ? 'success' : 'info';
            $scores_released = !$showAssignmentStatistics ? 'can' : 'cannot';
            $response['message'] = "Your students <strong>{$scores_released}</strong> view the assignment statistics.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error showing/hiding the assignment statistics for <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Course $course
     * @param Extension $extension
     * @param Score $Score
     * @param Submission $Submission
     * @param Solution $Solution
     * @param AssignmentGroup $AssignmentGroup
     * @param Assignment $assignment
     * @return array
     */
    public function index(Course $course,
                          Extension $extension,
                          Score $Score, Submission $Submission,
                          Solution $Solution,
                          AssignmentGroup $AssignmentGroup,
                          Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response = $assignment->getAssignmentsByCourse($course, $extension, $Score, $Submission, $Solution, $AssignmentGroup);

        return $response;
    }


    function getDefaultPointsPerQuestion(array $data)
    {
        return $data['source'] === 'a' ? $data['default_points_per_question'] : null;
    }

    function getDefaultClickerTimeToSubmit($assessment_type, $data)
    {
        return $assessment_type === 'clicker' ? $data['default_clicker_time_to_submit'] : null;

    }


    public function addAssignmentGroupWeight(Assignment $assignment, int $assignment_group_id, AssignmentGroupWeight $assignmentGroupWeight)
    {
        $assignment_group_weight_exists = AssignmentGroupWeight::where('course_id', $assignment->course->id)
            ->where('assignment_group_id', $assignment->assignment_group_id)
            ->get()
            ->isNotEmpty();

        if (!$assignment_group_weight_exists) {
            $assignmentGroupWeight->assignment_group_id = $assignment_group_id;
            $assignmentGroupWeight->course_id = $assignment->course->id;
            $assignmentGroupWeight->assignment_group_weight = 0;
            $assignmentGroupWeight->save();
        }
    }


    /**
     * @param StoreAssignment $request
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */

    public function store(StoreAssignment $request, Assignment $assignment, AssignmentGroupWeight $assignmentGroupWeight)
    {
        //Log::info('can log');
        $response['type'] = 'error';
        $course = Course::find(['course_id' => $request->input('course_id')])->first();
        $authorized = Gate::inspect('createCourseAssignment', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();

            $learning_tree_assessment = $request->assessment_type === 'learning tree';
            DB::beginTransaction();

            $assignment = Assignment::create(
                ['name' => $data['name'],
                    'available_from' => $this->formatDateFromRequest($request->available_from_date, $request->available_from_time),
                    'due' => $this->formatDateFromRequest($request->due_date, $request->due_time),
                    'source' => $data['source'],
                    'assessment_type' => $data['source'] === 'a' ? $request->assessment_type : 'delayed',
                    'min_time_needed_in_learning_tree' => $learning_tree_assessment ? $data['min_time_needed_in_learning_tree'] : null,
                    'percent_earned_for_exploring_learning_tree' => $learning_tree_assessment ? $data['percent_earned_for_exploring_learning_tree'] : null,
                    'submission_count_percent_decrease' => $learning_tree_assessment ? $data['submission_count_percent_decrease'] : null,
                    'instructions' => $request->instructions ? $request->instructions : '',
                    'external_source_points' => $data['source'] === 'x' ? $data['external_source_points'] : null,
                    'assignment_group_id' => $data['assignment_group_id'],
                    'default_points_per_question' => $this->getDefaultPointsPerQuestion($data),
                    'default_clicker_time_to_submit' => $this->getDefaultClickerTimeToSubmit($request->assessment_type, $data),
                    'scoring_type' => $data['scoring_type'],
                    'default_open_ended_submission_type' => $this->getDefaultOpenEndedSubmissionType($request, $data),
                    'default_open_ended_text_editor' => $this->getDefaultOpenEndedTextEditor($request, $data),
                    'late_policy' => $data['late_policy'],
                    'show_scores' => ($data['source'] === 'x' || ($data['source'] === 'a' && $request->assessment_type === 'delayed')) ? 0 : 1,
                    'solutions_released' => ($data['source'] === 'a' && $request->assessment_type === 'real time') ? 1 : 0,
                    'show_points_per_question' => ($data['source'] === 'x' || $request->assessment_type === 'delayed') ? 0 : 1,
                    'late_deduction_percent' => $data['late_deduction_percent'] ?? null,
                    'final_submission_deadline' => $this->getFinalSubmissionDeadline($request),
                    'late_deduction_application_period' => $this->getLateDeductionApplicationPeriod($request, $data),
                    'include_in_weighted_average' => $data['include_in_weighted_average'],
                    'course_id' => $course->id,
                    'notifications' => $data['notifications'],
                    'order' => $assignment->getNewAssignmentOrder($course)
                ]
            );

            $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);

            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>{$data['name']}</strong> has been created.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultOpenEndedTextEditor($request, $data)
    {
        if ($request->assessment_type !== 'delayed') {
            return null;
        } elseif (strpos($data['default_open_ended_submission_type'], 'text') !== false) {
            return str_replace(' text', '', $data['default_open_ended_submission_type']);
        } else {
            return null;
        }


    }

    /**
     * @param Request $request
     * @param array $data
     * @return int|mixed
     */
    public function getDefaultOpenEndedSubmissionType(Request $request, array $data)
    {
        if ($request->source === 'x' || $request->assessment_type !== 'delayed') {
            return 0;
        } elseif (strpos($data['default_open_ended_submission_type'], 'text') !== false) {
            return 'text';
        } else {
            return $data['default_open_ended_submission_type'];

        }
    }

    public function getLateDeductionApplicationPeriod(StoreAssignment $request, array $data)
    {
        if ($request->late_deduction_applied_once) {
            return 'once';
        }
        return $data['late_deduction_application_period'] ?? null;
    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function viewQuestionsInfo(Request $request, Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $can_view_assignment_statistics = Auth::user()->role === 2 || (Auth::user()->role === 3 && $assignment->students_can_view_assignment_statistics);
            $response['assignment'] = [
                'question_view' => $request->hasCookie('question_view') != false ? $request->cookie('question_view'): 'basic',
                'name' => $assignment->name,
                'assessment_type' => $assignment->assessment_type,
                'has_submissions_or_file_submissions' => $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty(),
                'time_left' => $this->getTimeLeft($assignment),
                'late_policy' => $assignment->late_policy,
                'past_due' => time() > strtotime($assignment->due),
                'total_points' => $this->getTotalPoints($assignment),
                'source' => $assignment->source,
                'default_clicker_time_to_submit' => $assignment->default_clicker_time_to_submit,
                'min_time_needed_in_learning_tree' => ($assignment->assessment_type === 'learning tree') ? $assignment->min_time_needed_in_learning_tree * 3000 : 0,//in milliseconds
                'percent_earned_for_exploring_learning_tree' => ($assignment->assessment_type === 'learning tree') ? $assignment->percent_earned_for_exploring_learning_tree : 0,
                'submission_files' => $assignment->submission_files,
                'show_points_per_question' => $assignment->show_points_per_question,
                'solutions_released' => $assignment->solutions_released,
                'show_scores' => $assignment->show_scores,
                'shown' => $assignment->shown,
                'submission_count_percent_decrease' => $assignment->submission_count_percent_decrease,
                'scoring_type' => $assignment->scoring_type,
                'students_can_view_assignment_statistics' => $assignment->students_can_view_assignment_statistics,
                'scores' => $can_view_assignment_statistics
                    ? $score->where('assignment_id', $assignment->id)->get()->pluck('score')
                    : []
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    /**
     *
     * Display the specified resource
     *
     * @param Assignment $assignment
     * @return Assignment
     */
    public function getQuestionsInfo(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getQuestionsInfo', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $response['assignment'] = [
                'name' => $assignment->name,
                'has_submissions' => $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty(),
                'submission_files' => $assignment->submission_files
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }


    /*
    * Display the specified resource
    *
    * @param Assignment $assignment
    * @return Assignment
    */
    public function scoresInfo(Assignment $assignment, Score $score)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('scoresInfo', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment = Assignment::find($assignment->id);
            $response['scores'] = $score->where('assignment_id', $assignment->id)->get()->pluck('score');
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
     */
    public function getInfoForGrading(Assignment $assignment)
    {

        $response['type'] = 'error';
        try {
            $assignment = Assignment::find($assignment->id);
            $sections = (Auth::user()->role === 2) ? $assignment->course->sections : $assignment->course->graderSections();

            $response['sections'] = [];
            foreach ($sections as $key => $section){
                $response['sections'][]=['name'=>$section->name,'id'=>$section->id];
            }

            $response['assignment'] = [
                'name' => $assignment->name,
                'late_policy' => $assignment->late_policy,
                'late_deduction_percent' => $assignment->late_deduction_percent,
                'late_deduction_application_period' => $assignment->late_deduction_application_period
            ];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }


    /**
     * @param Assignment $assignment
     * @param AssignmentGroup $assignmentGroup
     * @return array
     * @throws Exception
     */

    public function getAssignmentSummary(Assignment $assignment, AssignmentGroup $assignmentGroup)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentSummary', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $role = Auth::user()->role;
        try {
            $assignment = Assignment::find($assignment->id);
            $can_view_assignment_statistics = in_array($role, [2, 4])
                || ($role === 3 && $assignment->students_can_view_assignment_statistics);
            $response['assignment'] = $assignment->attributesToArray();

            $formatted_items = [
                'assignment_groups' => $assignmentGroup->assignmentGroupsByCourse($assignment->course->id),
                'total_points' => $this->getTotalPoints($assignment),
                'can_view_assignment_statistics' => $can_view_assignment_statistics,
                'formatted_late_policy' => $this->formatLatePolicy($assignment),
                'past_due' => time() > strtotime($assignment->due),
                'due' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assignment->due, Auth::user()->time_zone),
                'available_on' => $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assignment->available_from, Auth::user()->time_zone),
                'number_of_questions' => count($assignment->questions)
            ];
            foreach ($formatted_items as $key => $value) {
                $response['assignment'][$key] = $value;
            }
            $editing_form_items = $assignment->getEditingFormItems($assignment->available_from, $assignment->due, $assignment->final_submission_deadline, $assignment);
            foreach ($editing_form_items as $key => $value) {
                $response['assignment'][$key] = $value;
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";

        }
        return $response;
    }

    public function formatLatePolicy($assignment)
    {
        $late_policy = '';
        $final_submission_deadline = ($assignment->late_policy !== 'not accepted')
            ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assignment->final_submission_deadline, Auth::user()->time_zone)
            : '';
        switch ($assignment->late_policy) {
            case('not accepted'):
                $late_policy = "No late assignments are accepted.";
                break;
            case('marked late'):
                $late_policy = "Late assignments are marked late.  It is up to the instructor's discretion whether to apply a late penalty.";
                break;
            case('deduction'):
                if ($assignment->late_deduction_application_period === 'once') {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied once to any late assignment.";
                } else {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied every {$assignment->late_deduction_application_period} to any late assignment.";
                }
                break;
        }
        if ($assignment->late_policy !== 'not accepted') {
            $late_policy .= "  Students cannot submit assessments later than $final_submission_deadline.";
        }

        return $late_policy;
    }

    public function getTimeLeft(Assignment $assignment)
    {
        $Extension = new Extension();
        $extensions_by_user = $Extension->getUserExtensionsByAssignment(Auth::user());
        $due = $extensions_by_user[$assignment->id] ?? $assignment->due;
        $now = Carbon::now();
        return max($now->diffInMilliseconds(Carbon::parse($due), false), 0);

    }

    public function getTotalPoints(Assignment $assignment)
    {
        return DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->sum('points');

    }


    /**
     * @param StoreAssignment $request
     * @param Assignment $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */
    public function update(StoreAssignment $request, Assignment $assignment, AssignmentGroupWeight $assignmentGroupWeight)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();
            $data['assessment_type'] = ($request->assessment_type && $request->source === 'a') ? $request->assessment_type : '';
            $data['instructions'] = $request->instructions ? $request->instructions : '';
            $data['available_from'] = $this->formatDateFromRequest($request->available_from_date, $request->available_from_time);
            $default_open_ended_text_editor = $this->getDefaultOpenEndedTextEditor($request, $data);//do it this way because I reset the data
            $data['default_open_ended_text_editor'] = $default_open_ended_text_editor;
            $data['default_open_ended_submission_type'] = $this->getDefaultOpenEndedSubmissionType($request, $data);
            $data['default_clicker_time_to_submit'] = $this->getDefaultClickerTimeToSubmit($request->assessment_type, $data);
            $data['due'] = $this->formatDateFromRequest($request->due_date, $request->due_time);
            $data['final_submission_deadline'] = $this->getFinalSubmissionDeadline($request);
            $data['late_deduction_application_period'] = $this->getLateDeductionApplicationPeriod($request, $data);
            unset($data['available_from_date']);
            unset($data['available_from_time']);
            unset($data['open_ended_response']);
            //submissions exist so don't let them change the things below
            $data['default_points_per_question'] = $this->getDefaultPointsPerQuestion($data);
            if ($assignment->hasFileOrQuestionSubmissions()) {
                unset($data['scoring_type']);
                unset($data['default_points_per_question']);
                unset($data['submission_files']);
                unset($data['assessment_type']);
            }

            DB::beginTransaction();
            $assignment->update($data);

            $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>{$data['name']}</strong> has been updated.";
        } catch (Exception $e) {
            dB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating <strong>{$data['name']}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     *
     * Delete an assignment
     *
     * @param Course $course
     * @param Assignment $assignment
     * @param Score $score
     * @return mixed
     * @throws Exception
     */
    public function destroy(Assignment $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment_question_ids = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->get()
                ->pluck('id');

            DB::table('assignment_question_learning_tree')
                ->whereIn('assignment_question_id', $assignment_question_ids)
                ->delete();
            DB::table('assignment_question')->where('assignment_id', $assignment->id)->delete();
            DB::table('extensions')->where('assignment_id', $assignment->id)->delete();
            DB::table('scores')->where('assignment_id', $assignment->id)->delete();
            DB::table('submission_files')->where('assignment_id', $assignment->id)->delete();
            DB::table('submissions')->where('assignment_id', $assignment->id)->delete();
            DB::table('seeds')->where('assignment_id', $assignment->id)->delete();
            DB::table('cutups')->where('assignment_id', $assignment->id)->delete();
            DB::table('lti_launches')->where('assignment_id', $assignment->id)->delete();
            $course = $assignment->course;
            $number_with_the_same_assignment_group_weight = DB::table('assignments')
                ->where('course_id', $course->id)
                ->where('assignment_group_id', $assignment->assignment_group_id)
                ->select()
                ->get();
            if (count($number_with_the_same_assignment_group_weight) === 1) {
                DB::table('assignment_group_weights')
                    ->where('course_id', $course->id)
                    ->where('assignment_group_id', $assignment->assignment_group_id)
                    ->delete();
            }
            $assignments = $course->assignments->where('id', '<>', $assignment->id)
                ->pluck('id')
                ->toArray();
            $assignment->orderAssignments($assignments, $course);
            $assignment->delete();


            DB::commit();
            $response['type'] = 'success';
            $response['message'] = "The assignment <strong>$assignment->name</strong> has been deleted.";
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing <strong>$assignment->name</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getFinalSubmissionDeadline($request)
    {
        return $request->late_policy !== 'not accepted' ? $this->convertLocalMysqlFormattedDateToUTC($request->final_submission_deadline_date . ' ' . $request->final_submission_deadline_time, Auth::user()->time_zone) : null;
    }

    public function formatDateFromRequest($date, $time)
    {
        return $this->convertLocalMysqlFormattedDateToUTC("$date $time", Auth::user()->time_zone);
    }
}
