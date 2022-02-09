<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\BetaAssignment;
use App\BetaCourse;
use App\Helpers\Helper;
use App\Http\Controllers\Settings\PasswordController;
use App\Question;
use App\Section;
use App\SubmissionFile;
use App\Traits\DateFormatter;
use App\Course;
use App\Solution;
use App\Score;
use App\Extension;
use App\Submission;
use App\AssignmentGroup;
use App\AssignmentGroupWeight;
use App\Traits\S3;
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
    use S3;

    /**
     * @param Course $course
     * @return array
     * @throws Exception
     */
    public function getAssignmentsForAnonymousUser(Course $course): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentsForAnonymousUser', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignments = DB::table('assignments')
                ->where('course_id', $course->id)
                ->orderBy('order')
                ->get();
            $response['assignments'] = $assignments;
            $response['course_name'] = $course->name;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignments for this course.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function startPageInfo(Assignment $assignment)
    {
        $response['type'] = 'error';
        try {
            $libretexts_url = $assignment->libretexts_url;
            $response['adapt_launch'] = !$libretexts_url;
            $response['start_page_url'] = $libretexts_url;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment start page information.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getCommonsCourseAssignments(Course $course)
    {
        $response['type'] = 'error';
        if (User::where('email', 'commons@libretexts.org')->first()->id !== $course->user_id) {
            $response['message'] = 'You are not allowed to access the assignments in that course.';
            return $response;
        }
        try {
            $assignments = DB::table('assignments')
                ->leftJoin('assignment_question', 'assignments.id', '=', 'assignment_question.assignment_id')
                ->where('course_id', $course->id)
                ->select('assignments.id',
                    'assignments.id AS assignment_id',
                    'name',
                    'public_description AS description',
                    DB::raw("COUNT(assignment_question.question_id) as num_questions")
                )
                ->groupBy('assignments.id')
                ->orderBy('assignments.order')
                ->get();
            $response['assignments'] = $assignments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment information.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getAssignmentNamesForPublicCourse(Course $course, AssignmentGroup $assignmentGroup)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentNamesForPublicCourse', $course);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $assignment_groups = $assignmentGroup->assignmentGroupsByCourse($course->id);

        $num_questions = DB::table('assignment_question')
            ->whereIn('assignment_id', $course->assignments->pluck('id')->toArray())
            ->select('assignment_id', DB::raw("count(*) as num_questions"))
            ->groupBy('assignment_id')
            ->get();
        $num_questions_by_assignment_id = [];
        foreach ($num_questions as $num_question) {
            $num_questions_by_assignment_id[$num_question->assignment_id] = $num_question->num_questions;

        }
        try {
            $assignments = [];
            foreach ($course->assignments as $assignment) {
                $assignment->name = strpos($assignment->name, $assignment_groups[$assignment->id]) !== false
                    ? $assignment->name
                    : $assignment->name . " (" . $assignment_groups[$assignment->id] . ")";
                $assignments[] = $assignment;
                $assignment->num_questions = $num_questions_by_assignment_id[$assignment->id] ?? 0;
            }
            $response['assignments'] = $assignments;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment information.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function getAssignmentsAndUsers(Request $request, Course $course)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('getAssignmentsAndUsers', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignments = $course->assignments;
            $assignments_by_id[0] = ['value' => 0, 'text' => 'Please choose an assignment'];
            foreach ($assignments as $assignment) {
                $assignments_by_id[] = ['value' => $assignment->id, 'text' => $assignment->name];
            }
            $enrolled_users = $course->enrolledUsers;
            $users_by_id = [];
            foreach ($enrolled_users as $enrolled_user) {
                $users_by_id[] = [$enrolled_user->id, "$enrolled_user->first_name $enrolled_user->last_name", ''];
            }
            if (!$users_by_id) {
                $response['type'] = 'info';
                $response['message'] = 'This course has no students yet.';
                return $response;
            }
            usort($users_by_id, function ($a, $b) {
                return $a[1] <=> $b[1];
            });
            array_unshift($users_by_id, ['User Id', 'Name', 'Override Score']);
            $response['assignments'] = $assignments_by_id;
            $response['users'] = $users_by_id;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the user and assignment information.  Please try again or contact us for assistance.";
        }
        return $response;


    }

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
                $response['message'] = "You can't switch to an external assignment until you remove all ADAPT questions from the assignment.";
                return $response;
            }

            if ($assessment_type == 'clicker' && $open_ended !== null) {
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

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @return array
     * @throws \Throwable
     */


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
     * @param Assignment $assignment
     * @param Course $course
     * @param AssignmentGroup $assignmentGroup
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @return array
     * @throws Exception
     */
    public function importAssignment(Request                $request,
                                     Assignment             $assignment,
                                     Course                 $course,
                                     AssignmentGroup        $assignmentGroup,
                                     AssignmentSyncQuestion $assignmentSyncQuestion,
                                     AssignmentGroupWeight  $assignmentGroupWeight
    ): array
    {

        $response['type'] = 'error';
        $level = $request->level;

        $authorized = Gate::inspect('importAssignment', [$course, $assignment]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if (!in_array($level, ['properties_and_questions', 'properties_and_not_questions'])) {
            $response['message'] = "You should either choose 'properties and questions' or 'properties and not questions'.";
            return $response;

        }

        try {

            $assignment = Assignment::find($assignment->id);

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
                $assignmentSyncQuestion->importAssignmentQuestionsAndLearningTrees($assignment->id, $imported_assignment->id);
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
                                                   Course  $course)
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
    public function createAssignmentFromTemplate(Request                $request,
                                                 Assignment             $assignment,
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
            foreach ($assignment->course->assignments as $current_assignment) {
                if ($current_assignment->order > $assignment->order) {
                    $current_assignment->order++;
                    $current_assignment->save();
                }
            }
            $new_assignment = $assignment->replicate();
            $new_assignment->name = $new_assignment->name . " copy";
            $new_assignment->order++;
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
        if ($assignment->number_of_randomized_assessments && !$shown) {
            if ($assignment->questions->count() <= $assignment->number_of_randomized_assessments) {
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

    /**
     * @param Assignment $assignment
     * @param int $gradersCanSeeStudentNames
     * @return array
     * @throws Exception
     */
    public
    function gradersCanSeeStudentNames(Assignment $assignment, int $gradersCanSeeStudentNames): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('gradersCanSeeStudentNames', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $assignment->update(['graders_can_see_student_names' => !$gradersCanSeeStudentNames]);
            $response['type'] = !$gradersCanSeeStudentNames ? 'success' : 'info';
            $graders_can_see_student_names = !$gradersCanSeeStudentNames ? 'can' : 'cannot';
            $response['message'] = "Graders <strong>$graders_can_see_student_names</strong> see their students' names.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error changing the option of graders being able to view their students' names <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
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
    function showPointsPerQuestion(Assignment $assignment, int $showPointsPerQuestion)
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
    function index(Course          $course,
                   Extension       $extension,
                   Score           $Score, Submission $Submission,
                   Solution        $Solution,
                   AssignmentGroup $AssignmentGroup,
                   Assignment      $assignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $course);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        return $assignment->getAssignmentsByCourse($course, $extension, $Score, $Submission, $Solution, $AssignmentGroup);
    }


    function getDefaultPointsPerQuestion(array $data)
    {
        return $data['source'] === 'a' && $data['points_per_question'] === 'number of points' ? $data['default_points_per_question'] : null;
    }

    function getTotalAssignmentPoints(array $data)
    {
        return $data['source'] === 'a' && $data['points_per_question'] === 'question weight' ? $data['total_points'] : null;
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
     * @param BetaCourse $betaCourse
     * @return array
     * @throws Exception
     */

    public
    function store(StoreAssignment       $request,
                   Assignment            $assignment,
                   AssignmentGroupWeight $assignmentGroupWeight,
                   Section               $section,
                   User                  $user,
                   BetaCourse            $betaCourse)
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
            if ($course->alpha && $request->points_per_question === 'question weight') {
                $response['message'] = "Alpha courses cannot determine question points by weight.";
                return $response;
            }

            $learning_tree_assessment = $request->assessment_type === 'learning tree';


            DB::beginTransaction();

            $assignment = Assignment::create(
                [
                    'name' => $data['name'],
                    'public_description' => $request->public_description,
                    'private_description' => $request->private_description,
                    'source' => $data['source'],
                    'assessment_type' => $data['source'] === 'a' ? $request->assessment_type : 'delayed',
                    'number_of_allowed_attempts' => $this->getNumberOfAllowedAttempts($request),
                    'number_of_allowed_attempts_penalty' => $this->getNumberOfAllowedAttemptsPenalty($request),
                    'solutions_availability' => $this->getSolutionsAvailability($request),
                    'min_time_needed_in_learning_tree' => $learning_tree_assessment ? $data['min_time_needed_in_learning_tree'] : null,
                    'percent_earned_for_exploring_learning_tree' => $learning_tree_assessment ? $data['percent_earned_for_exploring_learning_tree'] : null,
                    'submission_count_percent_decrease' => $learning_tree_assessment ? $data['submission_count_percent_decrease'] : null,
                    'instructions' => $request->instructions ? $request->instructions : '',
                    'number_of_randomized_assessments' => $this->getNumberOfRandomizedAssessments($request->assessment_type, $data),
                    'external_source_points' => $data['source'] === 'x' ? $data['external_source_points'] : null,
                    'assignment_group_id' => $data['assignment_group_id'],
                    'points_per_question' => $this->getPointsPerQuestion($data),
                    'default_points_per_question' => $this->getDefaultPointsPerQuestion($data),
                    'total_points' => $this->getTotalAssignmentPoints($data),
                    'default_clicker_time_to_submit' => $this->getDefaultClickerTimeToSubmit($request->assessment_type, $data),
                    'scoring_type' => $data['scoring_type'],
                    'default_completion_scoring_mode' => Helper::getCompletionScoringMode($data['scoring_type'], $request->default_completion_scoring_mode, $request->completion_split_auto_graded_percentage),
                    'file_upload_mode' => $request->assessment_type === 'delayed' ? $data['file_upload_mode'] : null,
                    'default_open_ended_submission_type' => $this->getDefaultOpenEndedSubmissionType($request, $data),
                    'default_open_ended_text_editor' => $this->getDefaultOpenEndedTextEditor($request, $data),
                    'late_policy' => $data['late_policy'],
                    'show_scores' => ($data['source'] === 'x' || ($data['source'] === 'a' && $request->assessment_type === 'delayed')) ? 0 : 1,
                    'solutions_released' => 0,
                    'show_points_per_question' => ($data['source'] === 'x' || $request->assessment_type === 'delayed') ? 0 : 1,
                    'late_deduction_percent' => $data['late_deduction_percent'] ?? null,
                    'late_deduction_application_period' => $this->getLateDeductionApplicationPeriod($request, $data),
                    'include_in_weighted_average' => $data['include_in_weighted_average'],
                    'course_id' => $course->id,
                    'libretexts_url' => $data['libretexts_url'] ?? null,
                    'notifications' => $data['notifications'],
                    'order' => $assignment->getNewAssignmentOrder($course)
                ]
            );
            if ($course->alpha) {
                $beta_assign_tos[0] = $assign_tos[0];
                $beta_assign_tos[0]['groups'] = [];
                $beta_assign_tos[0]['groups'][0]['text'] = 'Everybody';

                $beta_courses = $betaCourse->where('alpha_course_id', $course->id)->get();
                foreach ($beta_courses as $beta_course) {
                    $beta_assignment = $assignment->replicate()->fill([
                        'course_id' => $beta_course->id
                    ]);
                    $beta_assignment->save();

                    $beta_assign_tos[0]['groups'][0]['value']['course_id'] = $beta_course->id;

                    BetaAssignment::create([
                        'id' => $beta_assignment->id,
                        'alpha_assignment_id' => $assignment->id
                    ]);

                    $this->addAssignTos($beta_assignment, $beta_assign_tos, $section, $user);

                }
            }

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
        } elseif ($data['file_upload_mode'] === 'combined_pdf') {
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
     * @param SubmissionFile $submissionFile
     * @return array
     * @throws Exception
     */
    public
    function viewQuestionsInfo(Request        $request,
                               Assignment     $assignment,
                               Score          $score,
                               SubmissionFile $submissionFile)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            $response['is_lms'] = (bool)$assignment->course->lms;//if no access, need this to determine whether to show the time zones
            return $response;
        }
        $is_fake_student = Auth::user()->fake_student;
        try {
            $assignment = Assignment::find($assignment->id);
            $can_view_assignment_statistics = Auth::user()->role === 2 || (Auth::user()->role === 3 && $assignment->students_can_view_assignment_statistics);
            $response['assignment'] = [
                'question_view' => $request->hasCookie('question_view') != false ? $request->cookie('question_view') : 'basic',
                'name' => $assignment->name,
                'assessment_type' => $assignment->assessment_type,
                'number_of_allowed_attempts' => $assignment->number_of_allowed_attempts,
                'number_of_allowed_attempts_penalty' => $assignment->number_of_allowed_attempts_penalty,
                'file_upload_mode' => $assignment->file_upload_mode,
                'has_submissions_or_file_submissions' => $assignment->hasNonFakeStudentFileOrQuestionSubmissions(),
                'time_left' => Auth::user()->role === 3 ? $this->getTimeLeft($assignment) : '',
                'late_policy' => $assignment->late_policy,
                'past_due' => Auth::user()->role === 3 ? time() > strtotime($assignment->assignToTimingByUser('due')) : '',
                'available' => !(Auth::user()->role === 3 && !$is_fake_student) || time() > strtotime($assignment->assignToTimingByUser('available_from')),
                'available_on' => (Auth::user()->role === 3 && !$is_fake_student) ? $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assignment->assignToTimingByUser('available_from'), Auth::user()->time_zone) : '',
                'total_points' => $this->getTotalPoints($assignment),
                'points_per_question' => $assignment->points_per_question,
                'source' => $assignment->source,
                'default_clicker_time_to_submit' => $assignment->default_clicker_time_to_submit,
                'min_time_needed_in_learning_tree' => $this->minTimeNeededInLearningTree($assignment),
                'percent_earned_for_exploring_learning_tree' => ($assignment->assessment_type === 'learning tree') ? $assignment->percent_earned_for_exploring_learning_tree : 0,
                'submission_files' => $assignment->submission_files,
                'show_points_per_question' => $assignment->show_points_per_question,
                'solutions_released' => $assignment->solutions_released,
                'show_scores' => $assignment->show_scores,
                'shown' => !(Auth::user()->role === 3 && !$is_fake_student) || $assignment->shown,
                'submission_count_percent_decrease' => $assignment->submission_count_percent_decrease,
                'scoring_type' => $assignment->scoring_type,
                'students_can_view_assignment_statistics' => $assignment->students_can_view_assignment_statistics,
                'scores' => $can_view_assignment_statistics
                    ? $score->where('assignment_id', $assignment->id)->get()->pluck('score')
                    : [],
                'beta_assignments_exist' => $assignment->betaAssignments() !== [],
                'is_beta_assignment' => $assignment->isBetaAssignment(),
                'is_lms' => (bool)$assignment->course->lms,
                'lti_launch_exists' => Auth::user()->role === 3 && !$is_fake_student && $assignment->ltiLaunchExists(Auth::user())
            ];

            if (Auth::user()->role === 3) {
                $response['assignment']['full_pdf_url'] = '';
                $submission_file = $submissionFile->where('user_id', $request->user()->id)
                    ->where('assignment_id', $assignment->id)
                    ->where('type', 'a')
                    ->first();
                if ($submission_file) {
                    $response['assignment']['full_pdf_url'] = $this->getTemporaryUrl($assignment->id, $submission_file->submission);
                }

            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function minTimeNeededInLearningTree(Assignment $assignment)
    {
        if ($assignment->assessment_type !== 'learning tree') {
            return 0;
        }
        if (session()->get('instructor_user_id')) {
            return 3000;
        }
        return $assignment->min_time_needed_in_learning_tree * 1000 * 60;
    }

    /**
     * @param Assignment $assignment
     * @return array
     * @throws Exception
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
                'has_submissions' => $assignment->hasNonFakeStudentFileOrQuestionSubmissions(),
                'submission_files' => $assignment->submission_files,
                'assessment_type' => $assignment->assessment_type
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
            $sections = [];
            switch (Auth::user()->role) {

                case(2):
                    $sections = $assignment->course->sections;
                    break;
                case(4):
                    $sections = $assignment->course->graderSections();
                    $access_level_override = $assignment->graders()
                        ->where('assignment_grader_access.user_id', Auth::user()->id)
                        ->first();
                    if ($access_level_override) {
                        $access_level = $access_level_override->pivot->access_level;
                        if ($access_level === 0) {
                            $sections = [];
                        }
                        if ($access_level === 1) {
                            $sections = $assignment->course->sections;
                        }
                    }
                    break;
            }


            $response['sections'] = [];
            $response['questions'] = [];
            foreach ($sections as $section) {
                $response['sections'][] = ['name' => $section->name, 'id' => $section->id];
            }

            $assignment_questions_where_student_can_upload_file = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->orderBy('order')
                ->get();
            foreach ($assignment_questions_where_student_can_upload_file as $question) {
                $response['questions'][] = ['text' => "$question->order", 'value' => $question->question_id];
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
     * @param SubmissionFile $submissionFile
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */

    public
    function getAssignmentSummary(Assignment             $assignment,
                                  AssignmentGroup        $assignmentGroup,
                                  SubmissionFile         $submissionFile,
                                  AssignmentSyncQuestion $assignmentSyncQuestion)
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
                'total_points' => Helper::removeZerosAfterDecimal(round($this->getTotalPoints($assignment),2)),
                'can_view_assignment_statistics' => $can_view_assignment_statistics,
                'number_of_questions' => count($assignment->questions),
                'number_of_randomized_questions_chosen' => $assignment->number_of_randomized_assessments
                    ?: "N/A"
            ];
            if (auth()->user()->role === 3) {
                $extension = DB::table('extensions')
                    ->select('extension')
                    ->where('assignment_id', $assignment->id)
                    ->where('user_id', auth()->user()->id)
                    ->first('extension');
                $formatted_items['is_instructor_logged_in_as_student'] = session()->get('instructor_user_id');
                $formatted_items['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
                $formatted_items['full_pdf_url'] = $submissionFile->getFullPdfUrl($assignment);
                $assign_to_timing = $assignment->assignToTimingByUser();
                $formatted_items['formatted_late_policy'] = $this->formatLatePolicy($assignment, $assign_to_timing);
                $formatted_items['past_due'] = time() > strtotime($assign_to_timing->due);
                $formatted_items['extension'] = $extension ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($extension->extension, Auth::user()->time_zone, 'F d, Y \a\t g:i a') : null;
                $formatted_items['due'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assign_to_timing->due, Auth::user()->time_zone);
                $formatted_items['formatted_due'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to_timing->due, Auth::user()->time_zone, 'F d, Y \a\t g:i a');
                $formatted_items['available_on'] = $this->convertUTCMysqlFormattedDateToLocalDateAndTime($assign_to_timing->available_from, Auth::user()->time_zone);

            } else {
                $lms = $assignment->course->lms;
                $formatted_items['lms'] = $lms;
                $formatted_items['is_beta_assignment'] = $assignment->isBetaAssignment();
                $formatted_items['is_alpha_course'] = (bool)$assignment->course->alpha;
                $formatted_items['course_end_date'] = $assignment->course->end_date;
                $formatted_items['course_start_date'] = $assignment->course->start_date;
                $formatted_items['assign_tos'] = $assignment->assignToGroups();
                $num_open = $num_closed = $num_upcoming = $num_assign_tos = 0;
                foreach ($formatted_items['assign_tos'] as $assign_to_key => $assign_to) {

                    $available_from = $assign_to['available_from'];
                    $due = $assign_to['due'];
                    $status = $assignment->getStatus($available_from, $due);

                    switch ($status) {
                        case('Open'):
                            $num_open++;
                            break;
                        case('Closed'):
                            $num_closed++;
                            break;
                        case('Upcoming'):
                            $num_upcoming++;
                    }
                    $num_assign_tos++;
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
                $formatted_items['overall_status'] = $assignment->getOverallStatus($num_assign_tos, $num_open, $num_closed, $num_upcoming);

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
            ? $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($assign_to_timing->final_submission_deadline, Auth::user()->time_zone, 'F d, Y \a\t g:i a')
            : '';
        switch ($assignment->late_policy) {
            case('not accepted'):
                $late_policy = "No late submissions are accepted.";
                break;
            case('marked late'):
                $late_policy = "Late submissions are marked late.  It is up to the instructor's discretion whether to apply a late penalty.";
                break;
            case('deduction'):
                if ($assignment->late_deduction_application_period === 'once') {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied once to any late submission.  ";
                } else {
                    $late_policy = "A deduction of {$assignment->late_deduction_percent}% is applied every {$assignment->late_deduction_application_period} to any late submission.  ";
                }
                $late_policy .= "This penalty is applied to the latest submission up until the final submission deadline.";
                break;
        }
        if (($assignment->late_policy !== 'not accepted') && ($assign_to_timing !== null)) {
            $late_policy .= "  Students cannot submit responses later than $final_submission_deadline.";
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
        $total_points = $assignment->number_of_randomized_assessments
            ? $assignment->number_of_randomized_assessments * $assignment->default_points_per_question
            : DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->sum('points');
        return Helper::removeZerosAfterDecimal(round($total_points, 4));
    }


    /**
     * @param StoreAssignment $request
     * @param Assignment $assignment
     * @param AssignmentGroupWeight $assignmentGroupWeight
     * @param Section $section
     * @param User $user
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function update(StoreAssignment        $request,
                    Assignment             $assignment,
                    AssignmentGroupWeight  $assignmentGroupWeight,
                    Section                $section,
                    User                   $user,
                    AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {

            $data = $request->validated();
            if ($assignment->assessment_type !== $request->assessment_type) {
                $message = $this->validAssessmentTypeSwitch($assignment, $request->assessment_type);
                if ($message) {
                    $response['message'] = $message;
                    $response['timeout'] = 12000;
                    return $response;
                }
            }


            $assign_tos = $request->assign_tos;
            $repeated_groups = $this->groupsMustNotRepeat($assign_tos);
            if ($repeated_groups) {
                $response['message'] = $repeated_groups;
                return $response;
            }

            if ($assignment->course->alpha && $request->points_per_question === 'question weight') {
                $response['message'] = "Alpha courses cannot determine question points by weight.";
                return $response;
            }

            $assignments = $assignment->course->alpha
                ? $assignment->addBetaAssignments()
                : [$assignment];


            DB::beginTransaction();
            if ($assignment->points_per_question !== $request->points_per_question) {
                $message = $this->validPointsPerQuestionSwitch($assignment);
                if ($message) {
                    $response['message'] = $message;
                    return $response;
                }
                if (count($assignments) > 1) {
                    $response['message'] = "This is an Alpha assignment with tethered Beta assignments so you cannot switch the Points Per Question value.";
                    return $response;
                }
                $assignmentSyncQuestion->switchPointsPerQuestion($assignment, $request->total_points);
            }
            if ($assignment->points_per_question === 'question weight' && round($assignment->total_points,4) !== round($request->total_points,4)) {
                if (count($assignments) > 1) {
                    $response['message'] = "This is an Alpha assignment with tethered Beta assignments so you cannot update the Total Points per Assignment.";
                    return $response;
                }
                $assignment->scaleColumnsWithNewTotalPoints($request->total_points);
            }
            foreach ($assignments as $assignment) {
                if (!$assignment->isBetaAssignment()) {
                    //either the alpha assignment, so set these
                    //OR it's just a regular assignment so set these

                    $data['number_of_allowed_attempts'] = $this->getNumberOfAllowedAttempts($request);
                    $data['number_of_allowed_attempts_penalty'] = $this->getNumberOfAllowedAttemptsPenalty($request);
                    $data['public_description'] = $request->public_description;
                    $data['private_description'] = $request->private_description;
                    $data['assessment_type'] = ($request->assessment_type && $request->source === 'a') ? $request->assessment_type : '';
                    $data['instructions'] = $request->instructions ? $request->instructions : '';
                    $default_open_ended_text_editor = $this->getDefaultOpenEndedTextEditor($request, $data);//do it this way because I reset the data
                    $data['default_open_ended_text_editor'] = $default_open_ended_text_editor;
                    $data['default_open_ended_submission_type'] = $this->getDefaultOpenEndedSubmissionType($request, $data);
                    $data['default_clicker_time_to_submit'] = $this->getDefaultClickerTimeToSubmit($request->assessment_type, $data);
                    $data['number_of_randomized_assessments'] = $this->getNumberOfRandomizedAssessments($request->assessment_type, $data);
                    $data['file_upload_mode'] = $request->assessment_type === 'delayed' ? $data['file_upload_mode'] : null;
                    $data['points_per_question'] = $this->getPointsPerQuestion($data);
                    $data['default_points_per_question'] = $this->getDefaultPointsPerQuestion($data);
                    $data['total_points'] = $this->getTotalAssignmentPoints($data);
                    $data['default_completion_scoring_mode'] = Helper::getCompletionScoringMode($request->scoring_type, $request->default_completion_scoring_mode, $request->completion_split_auto_graded_percentage);

                }
                $data['late_deduction_application_period'] = $this->getLateDeductionApplicationPeriod($request, $data);

                //submissions exist so don't let them change the things below
                if (isset($assign_tos)) {
                    unset($data['available_from_date']);
                    unset($data['available_from_time']);
                    unset($data['final_submission_deadline']);
                    unset($data['open_ended_response']);
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
                }
                $assignment->update($data);
                $this->addAssignmentGroupWeight($assignment, $data['assignment_group_id'], $assignmentGroupWeight);
                if (isset($assign_tos)) {
                    $this->addAssignTos($assignment, $assign_tos, $section, $user);
                }
                unset($assign_tos);//should just be done for the alpha course if it is an alpha course
            }
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

    public function getSolutionsAvailability($request)
    {
        return $request->assessment_type === 'real time' ? $request->solutions_availability : null;
    }


    public function getNumberOfAllowedAttempts($request)
    {
        return $request->assessment_type === 'real time' && $request->scoring_type === 'p' ? $request->number_of_allowed_attempts : null;
    }

    public function getNumberOfAllowedAttemptsPenalty($request)
    {

        return $request->assessment_type === 'real time' && $request->scoring_type === 'p' &&  (int)$request->number_of_allowed_attempts !== 1
            ? str_replace('%', '', $request->number_of_allowed_attempts_penalty)
            : null;
    }

    /**
     *
     * Delete an assignment
     *
     * @param Assignment $assignment
     * @param AssignToTiming $assignToTiming
     * @param BetaAssignment $betaAssignment
     * @return array
     * @throws Exception
     */
    public
    function destroy(Assignment     $assignment,
                     AssignToTiming $assignToTiming,
                     BetaAssignment $betaAssignment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        if ($betaAssignment->where('alpha_assignment_id', $assignment->id)->first()) {
            $response['message'] = "You cannot delete an Alpha assignment with tethered Beta assignments.";
            return $response;
        }

        try {
            DB::beginTransaction();
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
            DB::table('can_give_ups')->where('assignment_id', $assignment->id)->delete();
            DB::table('seeds')->where('assignment_id', $assignment->id)->delete();
            DB::table('cutups')->where('assignment_id', $assignment->id)->delete();
            DB::table('lti_launches')->where('assignment_id', $assignment->id)->delete();
            DB::table('randomized_assignment_questions')->where('assignment_id', $assignment->id)->delete();
            DB::table('compiled_pdf_overrides')->where('assignment_id', $assignment->id)->delete();
            DB::table('question_level_overrides')->where('assignment_id', $assignment->id)->delete();
            DB::table('assignment_level_overrides')->where('assignment_id', $assignment->id)->delete();
            $assignment->graders()->detach();
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

    /**
     * @param Assignment $assignment
     * @return string
     */
    public function validPointsPerQuestionSwitch(Assignment $assignment): string
    {

        if ($assignment->course->alpha) {
            $message = $assignment->hasNonFakeStudentFileOrQuestionSubmissions($assignment->addBetaAssignmentIds())
                ? "This assignment is in an Alpha course with Beta course submissions so you can't change the way that points are computed."
                : '';

        } else {
            $message = $assignment->hasNonFakeStudentFileOrQuestionSubmissions()
                ? "This assignment already has submissions so you can't change the way that points are computed."
                : '';

        }
        return $message;
    }

    /**
     * @param Assignment $assignment
     * @param $new_assessment_type
     * @return string
     */
    public function validAssessmentTypeSwitch(Assignment $assignment, $new_assessment_type): string
    {
        $has_questions = count($assignment->questions) > 0;

        $message = '';
        if ($has_questions) {
            switch ($assignment->assessment_type) {
                case('learning tree'):
                    $message = "This assignment already has non-Learning Tree assessments in it.  If you would like to change the assessment type, please first remove those assessments.";
                    break;
                case('delayed'):
                    if ($new_assessment_type == 'clicker') {
                        $new_assessment_type = ucfirst($new_assessment_type);
                        foreach ($assignment->questions as $question) {
                            if (!$question->technology_iframe) {
                                $message = "If you would like to change this assignment to $new_assessment_type, all of your assessments must have an associated auto-graded component H5P or Webwork.  Please remove any assessments that don't have auto-graded component.";
                                break;
                            }
                        }
                        $open_ended_submissions = DB::table('assignment_question')
                            ->where('assignment_id', $assignment->id)
                            ->where('open_ended_submission_type', '<>', '0')
                            ->first();
                        if ($open_ended_submissions) {
                            $message = "If you would like to change this assignment to $new_assessment_type, please first remove any assessments that require an open-ended submission.";
                        }
                    }
                    if ($new_assessment_type === 'learning tree') {
                        $message = "You can't switch from a Delayed to a Learning Tree assessment type until you remove all current assessments.";
                    }
                    break;
                case('clicker'):
                case('real time'):
                    if ($new_assessment_type === 'learning tree') {
                        $message = "Please first remove all Learning Tree assessments before choosing this option.";
                    }
                    break;
            }
        }

        return $message;

    }

    /**
     * @param string $assessment_type
     * @param array $data
     * @return mixed|null
     */
    public function getNumberOfRandomizedAssessments($assessment_type, array $data)
    {
        if ($assessment_type === 'clicker') {
            return null;
        }
        return $data['number_of_randomized_assessments'] ?? null;
    }

    public function getPointsPerQuestion($data)
    {
        return $data['source'] === 'a' ? $data['points_per_question'] : null;
    }


}
