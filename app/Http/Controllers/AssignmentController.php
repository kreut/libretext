<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\Section;
use App\Traits\DateFormatter;
use App\Course;
use App\Solution;
use App\Score;
use App\Extension;
use App\Submission;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\User;
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
     * @param AssignToTiming $assignToTiming
     * @param AssignToGroup $assignToGroup
     * @return array
     * @throws Exception
     */
    public function importAssignment(Request $request,
                                     Course $course,
                                     Assignment $assignment,
                                     AssignmentGroup $assignmentGroup,
                                     AssignmentSyncQuestion $assignmentSyncQuestion,
                                     AssignmentGroupWeight $assignmentGroupWeight,
                                     AssignToTiming $assignToTiming,
                                     AssignToGroup $assignToGroup
    )
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
            $assignment->saveAssignmentTimingAndGroup($imported_assignment);

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


    public function getImportableAssignmentsByUser(Request $request,
                                                   Course $course)
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

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function createAssignmentFromTemplate(Request $request,
                                                 Assignment $assignment,
                                                 AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('createFromTemplate', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            $assignment = Assignment::find($assignment->id);
            $new_assignment = $assignment->replicate();
            $new_assignment->name = $new_assignment->name . " copy";
            $new_assignment->save();

            if ($request->level === 'properties_and_questions') {
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $new_assignment->id);
            }


            if ((int)$request->assign_to_groups === 1) {
                $this->copyAssignTos($assignment, $new_assignment);

            } else {
                $assignment->saveAssignmentTimingAndGroup($new_assignment);
            }

            DB::commit();
            $response['message'] = "<strong>$new_assignment->name</strong> is using the same template as <strong>$assignment->name</strong>. Don't forget to add questions and update the assignment's dates.";
            $response['type'] = 'success';
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating an assignment from {$assignment->name}.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public
    function solutionsReleased(Request $request, Assignment $assignment, int $solutionsReleased)
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

    public
    function showAssignment(Request $request, Assignment $assignment, int $shown)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('showAssignment', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        if ($assignment->number_of_randomized_assessments && !$shown){
            if ($assignment->questions->count() <= $assignment->number_of_randomized_assessments){
                $response['message'] = "Before you can show this assignment, please make sure that the number of chosen assessments ({$assignment->questions->count()}) is greater than the number of randomized assessments ({$assignment->number_of_randomized_assessments}).";
           return $response;
            }
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


    public
    function showScores(Request $request, Assignment $assignment, int $showScores)
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
    public
    function getActiveExtensionMessage(Assignment $assignment, string $type)
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

    public
    function showPointsPerQuestion(Request $request, Assignment $assignment, int $showPointsPerQuestion)
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


    public
    function showAssignmentStatistics(Request $request, Assignment $assignment, int $showAssignmentStatistics)
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
    public
    function index(Course $course,
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


    public
    function addAssignmentGroupWeight(Assignment $assignment, int $assignment_group_id, AssignmentGroupWeight $assignmentGroupWeight)
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
     * @param Assignment $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param Section $section
     * @param User $user
     * @return array
     * @throws Exception
     */

    public
    function store(StoreAssignment $request, Assignment $assignment,
                   AssignmentGroupWeight $assignmentGroupWeight,
                   Section $section,
                   User $user)
    {
        $response['type'] = 'error';
        $course = Course::find(['course_id' => $request->input('course_id')])->first();
        $authorized = Gate::inspect('createCourseAssignment', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();
            $assign_tos = $request->assign_tos;

            $repeated_groups = $this->groupsMustNotRepeat($assign_tos);
            if ($repeated_groups) {
                $response['message'] = $repeated_groups;
                return $response;
            }
            $learning_tree_assessment = $request->assessment_type === 'learning tree';
            DB::beginTransaction();

            $assignment = Assignment::create(
                [
                    'name' => $data['name'],
                    'source' => $data['source'],
                    'assessment_type' => $data['source'] === 'a' ? $request->assessment_type : 'delayed',
                    'min_time_needed_in_learning_tree' => $learning_tree_assessment ? $data['min_time_needed_in_learning_tree'] : null,
                    'percent_earned_for_exploring_learning_tree' => $learning_tree_assessment ? $data['percent_earned_for_exploring_learning_tree'] : null,
                    'submission_count_percent_decrease' => $learning_tree_assessment ? $data['submission_count_percent_decrease'] : null,
                    'instructions' => $request->instructions ? $request->instructions : '',
                    'number_of_randomized_assessments' => $data['number_of_randomized_assessments'] ?? null,
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
                    'late_deduction_application_period' => $this->getLateDeductionApplicationPeriod($request, $data),
                    'include_in_weighted_average' => $data['include_in_weighted_average'],
                    'course_id' => $course->id,
                    'notifications' => $data['notifications'],
                    'order' => $assignment->getNewAssignmentOrder($course)
                ]
            );

            $this->addAssignTos($assignment, $assign_tos, $section, $user);

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

    public
    function copyAssignTos(Assignment $assignment, Assignment $new_assignment)
    {

        foreach ($assignment->assignToTimings as $assignToTiming) {
            $newAssignToTiming = new AssignToTiming();
            $newAssignToTiming->assignment_id = $new_assignment->id;
            $newAssignToTiming->available_from = $assignToTiming->available_from;
            $newAssignToTiming->due = $assignToTiming->due;
            $newAssignToTiming->final_submission_deadline = $assignToTiming->final_submission_deadline;
            $newAssignToTiming->save();
            $assign_to_groups = $assignToTiming->assignToGroups;
            foreach ($assign_to_groups as $assign_to_group) {
                $newAssignToGroup = new AssignToGroup();
                $newAssignToGroup->group = $assign_to_group->group;
                $newAssignToGroup->group_id = $assign_to_group->group_id;
                $newAssignToGroup->assign_to_timing_id = $newAssignToTiming->id;
                $newAssignToGroup->save();
            }
            foreach ($assignToTiming->assignToUsers as $assignToUser) {
                $newAssignToUser = new AssignToUser();
                $newAssignToUser->assign_to_timing_id = $newAssignToTiming->id;
                $newAssignToUser->user_id = $assignToUser->user_id;
                $newAssignToUser->save();
            }
        }


    }

    public
    function addAssignTos(Assignment $assignment, array $assign_tos, Section $section, User $user)
    {


        $assign_to_timings = AssignToTiming::where('assignment_id', $assignment->id)->get();
        if ($assign_to_timings->isNotEmpty()) {
            //remove the old ones
            foreach ($assign_to_timings as $assign_to_timing) {
                AssignToGroup::where('assign_to_timing_id', $assign_to_timing->id)->delete();
                AssignToUser::where('assign_to_timing_id', $assign_to_timing->id)->delete();
                $assign_to_timing->delete();
            }
        }

        $assign_to_timings = [];

        foreach ($assign_tos as $assign_to) {
            $assignToTiming = new AssignToTiming();
            $assignToTiming->assignment_id = $assignment->id;
            $assignToTiming->available_from = $this->formatDateFromRequest($assign_to['available_from_date'], $assign_to['available_from_time']);
            $assignToTiming->due = $this->formatDateFromRequest($assign_to['due_date'], $assign_to['due_time']);
            $assignToTiming->final_submission_deadline = $assignment->late_policy !== 'not accepted'
                ? $this->formatDateFromRequest($assign_to['final_submission_deadline_date'], $assign_to['final_submission_deadline_time'])
                : null;
            $assignToTiming->save();
            $assign_to_timings[] = $assignToTiming->id;
        }
        $assigned_users = [];
        $enrolled_users_by_course = $assignment->course->enrolledUsersWithFakeStudent->pluck('id')->toArray();
        foreach ($assign_tos as $key => $assign_to) {

            foreach ($assign_to['groups'] as $group) {
                if (isset($group['value']['user_id'])) {
                    $user_id = $group['value']['user_id'];
                    $this->saveAssignToGroup('user', $user_id, $assign_to_timings[$key]);
                    $this->saveAssignToUser($user_id, $assign_to_timings[$key]);
                    $assigned_users[] = $user_id;
                }
            }
        }

        foreach ($assign_tos as $key => $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                if (isset($group['value']['section_id'])) {
                    $section_id = $group['value']['section_id'];
                    $assign_to_section = Section::find($section_id);
                    $this->saveAssignToGroup('section', $assign_to_section->id, $assign_to_timings[$key]);
                    $enrolled_users_by_section = $assign_to_section->enrolledUsers;
                    foreach ($enrolled_users_by_section as $enrolled_user) {
                        if (!in_array($enrolled_user->id, $assigned_users)) {
                            $this->saveAssignToUser($enrolled_user->id, $assign_to_timings[$key]);
                            $assigned_users[] = $enrolled_user->id;
                        }
                    }
                }
            }
        }

        foreach ($assign_tos as $key => $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                if (isset($group['value']['course_id'])) {
                    $this->saveAssignToGroup('course', $assignment->course->id, $assign_to_timings[$key]);
                    foreach ($enrolled_users_by_course as $enrolled_user_id) {
                        if (!in_array($enrolled_user_id, $assigned_users)) {
                            $this->saveAssignToUser($enrolled_user_id, $assign_to_timings[$key]);
                            $assigned_users[] = $enrolled_user_id;
                        }
                    }
                }
            }
        }

    }

    function saveAssignToUser(int $user_id, int $assign_to_timing_id)
    {
        $assignToUser = new AssignToUser();
        $assignToUser->assign_to_timing_id = $assign_to_timing_id;
        $assignToUser->user_id = $user_id;
        $assignToUser->save();
    }

    function saveAssignToGroup(string $group, int $group_id, int $assign_to_timing_id)
    {
        $assignToGroup = new AssignToGroup();
        $assignToGroup->group_id = $group_id;
        $assignToGroup->group = $group;
        $assignToGroup->assign_to_timing_id = $assign_to_timing_id;
        $assignToGroup->save();
    }

    public
    function removeDuplicateUsers($enrolled_users, $assigned_users)
    {
        foreach ($enrolled_users as $key => $enrolled_user) {
            if (in_array($enrolled_user->user_id, $assigned_users)) {
                $enrolled_users->forget($key);
            }
        }
        return $enrolled_users;
    }

    public
    function getDefaultOpenEndedTextEditor($request, $data)
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
    public
    function getDefaultOpenEndedSubmissionType(Request $request, array $data)
    {
        if ($request->source === 'x' || $request->assessment_type !== 'delayed') {
            return 0;
        } elseif (strpos($data['default_open_ended_submission_type'], 'text') !== false) {
            return 'text';
        } else {
            return $data['default_open_ended_submission_type'];

        }
    }

    public
    function getLateDeductionApplicationPeriod(StoreAssignment $request, array $data)
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
    public
    function viewQuestionsInfo(Request $request, Assignment $assignment, Score $score)
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
                'question_view' => $request->hasCookie('question_view') != false ? $request->cookie('question_view') : 'basic',
                'name' => $assignment->name,
                'assessment_type' => $assignment->assessment_type,
                'has_submissions_or_file_submissions' => $assignment->submissions->isNotEmpty() + $assignment->fileSubmissions->isNotEmpty(),
                'time_left' => Auth::user()->role === 3 ? $this->getTimeLeft($assignment) : '',
                'late_policy' => $assignment->late_policy,
                'past_due' => Auth::user()->role === 3 ? time() > strtotime($assignment->assignToTimingByUser('due')) : '',
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
    public
    function getQuestionsInfo(Assignment $assignment)
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
    public
    function scoresInfo(Assignment $assignment, Score $score)
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
    public
    function getInfoForGrading(Assignment $assignment)
    {

        $response['type'] = 'error';
        try {
            $assignment = Assignment::find($assignment->id);
            $sections = (Auth::user()->role === 2) ? $assignment->course->sections : $assignment->course->graderSections();

            $response['sections'] = [];
            foreach ($sections as $key => $section) {
                $response['sections'][] = ['name' => $section->name, 'id' => $section->id];
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

    public
    function groupsMustNotRepeat($assign_tos)
    {
        $used_groups = [];
        $message = null;
        foreach ($assign_tos as $assign_to) {
            foreach ($assign_to['groups'] as $group) {
                if (in_array($group, $used_groups)) {
                    $message = "{$group['text']} was chosen twice as an assign to.";
                }
                $used_groups[] = $group;
            }
        }
        return $message;
    }

    /**
     * @param Assignment $assignment
     * @param AssignmentGroup $assignmentGroup
     * @return array
     * @throws Exception
     */

    public
    function getAssignmentSummary(Assignment $assignment, AssignmentGroup $assignmentGroup)
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
                'number_of_questions' => count($assignment->questions),
                'number_of_randomized_questions_chosen' => $assignment->number_of_randomized_assessments
                                    ? $assignment->number_of_randomized_assessments : "none"
            ];
            if (auth()->user()->role === 3) {
                $assign_to_timing = $assignment->assignToTimingByUser();
                $formatted_items['formatted_late_policy'] = $this->formatLatePolicy($assignment, $assign_to_timing);
                $formatted_items['past_due'] = time() > strtotime($assign_to_timing->due);
                $formatted_items['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assign_to_timing->due, Auth::user()->time_zone);
                $formatted_items['available_on'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assign_to_timing->available_from, Auth::user()->time_zone);

            } else {

                $formatted_items['course_end_date'] = $assignment->course->end_date;
                $formatted_items['course_start_date'] = $assignment->course->start_date;
                $formatted_items['assign_tos'] = $assignment->assignToGroups();
                foreach ($formatted_items['assign_tos'] as $assign_to_key => $assign_to) {
                    $available_from = $assign_to['available_from'];
                    $due = $assign_to['due'];
                    $formatted_items['formatted_late_policy'] = $this->formatLatePolicy($assignment, null);
                    $final_submission_deadline = $assign_to['final_submission_deadline'];
                    $formatted_items['assign_tos'][$assign_to_key]['status'] = $assignment->getStatus($available_from, $due);
                    $formatted_items['assign_tos'][$assign_to_key]['available_from_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($available_from, Auth::user()->time_zone);
                    $formatted_items['assign_tos'][$assign_to_key]['available_from_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($available_from, Auth::user()->time_zone);
                    $formatted_items['assign_tos'][$assign_to_key]['final_submission_deadline_date'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalDate($final_submission_deadline, Auth::user()->time_zone) : null;
                    $formatted_items['assign_tos'][$assign_to_key]['final_submission_deadline_time'] = $final_submission_deadline ? $this->convertUTCMysqlFormattedDateToLocalTime($final_submission_deadline, Auth::user()->time_zone) : null;
                    $formatted_items['assign_tos'][$assign_to_key]['due_date'] = $this->convertUTCMysqlFormattedDateToLocalDate($due, Auth::user()->time_zone);
                    $formatted_items['assign_tos'][$assign_to_key]['due_time'] = $this->convertUTCMysqlFormattedDateToLocalTime($due, Auth::user()->time_zone);
                }
            }
            foreach ($formatted_items as $key => $value) {
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

    public
    function formatLatePolicy($assignment, $assign_to_timing)
    {
        //$assign_to_timing will be appropriate for students
        $late_policy = '';
        $final_submission_deadline = ($assignment->late_policy !== 'not accepted') && ($assign_to_timing !== null)
            ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to_timing->final_submission_deadline, Auth::user()->time_zone)
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
        if (($assignment->late_policy !== 'not accepted') && ($assign_to_timing !== null)) {
            $late_policy .= "  Students cannot submit assessments later than $final_submission_deadline.";
        }

        return $late_policy;
    }

    public
    function getTimeLeft(Assignment $assignment)
    {
        $Extension = new Extension();
        $extensions_by_user = $Extension->getUserExtensionsByAssignment(Auth::user());
        $due = $extensions_by_user[$assignment->id] ?? $assignment->assignToTimingByUser('due');
        $now = Carbon::now();
        return max($now->diffInMilliseconds(Carbon::parse($due), false), 0);

    }

    public
    function getTotalPoints(Assignment $assignment)
    {
        return $assignment->number_of_randomized_assessments
            ? $assignment->number_of_randomized_assessments * $assignment->default_points_per_question
            : DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->sum('points');

    }


    /**
     * @param StoreAssignment $request
     * @param Assignment $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param Section $section
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function update(StoreAssignment $request,
                    Assignment $assignment,
                    AssignmentGroupWeight $assignmentGroupWeight,
                    Section $section,
                    User $user)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();
            $assign_tos = $request->assign_tos;
            $repeated_groups = $this->groupsMustNotRepeat($assign_tos);
            if ($repeated_groups) {
                $response['message'] = $repeated_groups;
                return $response;
            }

            $data['assessment_type'] = ($request->assessment_type && $request->source === 'a') ? $request->assessment_type : '';
            $data['instructions'] = $request->instructions ? $request->instructions : '';
            $default_open_ended_text_editor = $this->getDefaultOpenEndedTextEditor($request, $data);//do it this way because I reset the data
            $data['default_open_ended_text_editor'] = $default_open_ended_text_editor;
            $data['default_open_ended_submission_type'] = $this->getDefaultOpenEndedSubmissionType($request, $data);
            $data['default_clicker_time_to_submit'] = $this->getDefaultClickerTimeToSubmit($request->assessment_type, $data);
            $data['late_deduction_application_period'] = $this->getLateDeductionApplicationPeriod($request, $data);
            $data['number_of_randomized_assessments'] = $data['number_of_randomized_assessments'] ?? null;
            unset($data['available_from_date']);
            unset($data['available_from_time']);
            unset($data['final_submission_deadline']);
            unset($data['open_ended_response']);
            //submissions exist so don't let them change the things below
            $data['default_points_per_question'] = $this->getDefaultPointsPerQuestion($data);
            if ($assignment->hasFileOrQuestionSubmissions()) {
                unset($data['scoring_type']);
                unset($data['default_points_per_question']);
                unset($data['submission_files']);
                unset($data['assessment_type']);
            }
            foreach ($assign_tos as $key => $assign_to) {
                unset($data['groups_' . $key]);
                unset($data['due_' . $key]);
                unset($data['final_submission_deadline_' . $key]);
                unset($data['available_from_date_' . $key]);
                unset($data['available_from_time_' . $key]);
                unset($data['due_time_' . $key]);
                unset($data['final_submission_deadline_date' . $key]);
                unset($data['final_submission_deadline_time_' . $key]);
            }

            DB::beginTransaction();
            $assignment->update($data);

            $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);
            $this->addAssignTos($assignment, $assign_tos, $section, $user);
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
    public
    function destroy(Assignment $assignment, AssignToTiming $assignToTiming)
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
            $assignToTiming->deleteTimingsGroupsUsers($assignment);

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


    public
    function formatDateFromRequest($date, $time)
    {
        return $this->convertLocalMysqlFormattedDateToUTC("$date $time", Auth::user()->time_zone);
    }
}
