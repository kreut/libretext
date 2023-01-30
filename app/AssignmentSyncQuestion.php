<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Jobs\ProcessPassBackByUserIdAndAssignment;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AssignmentSyncQuestion extends Model
{
    protected $table = 'assignment_question';

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param BetaCourseApproval $betaCourseApproval
     * @return int
     * @throws Exception
     */
    public function store(Assignment         $assignment,
                          Question           $question,
                          BetaCourseApproval $betaCourseApproval): int
    {


        $points = $assignment->points_per_question === 'number of points'
            ? $assignment->default_points_per_question
            : 0;
        $open_ended_submission_type = $assignment->default_open_ended_submission_type;
        $open_ended_text_editor = $assignment->default_open_ended_text_editor;
        if ($assignment->isBetaAssignment()) {
            $alpha_assignment_id = BetaAssignment::find($assignment->id)->alpha_assignment_id;
            $alpha_assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $alpha_assignment_id)
                ->where('question_id', $question->id)
                ->first();
            $points = $alpha_assignment_question->points;
            $open_ended_submission_type = $alpha_assignment_question->open_ended_submission_type;
            $open_ended_text_editor = $alpha_assignment_question->open_ended_text_editor;
        }
        $assignment_question_id = DB::table('assignment_question')
            ->insertGetId([
                'assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'order' => $this->getNewQuestionOrder($assignment),
                'points' => $points, //don't need to test since tested already when creating an assignment
                'weight' => $assignment->points_per_question === 'number of points' ? null : 1,
                'completion_scoring_mode' => $assignment->scoring_type === 'c' ? $assignment->default_completion_scoring_mode : null,
                'open_ended_submission_type' => $open_ended_submission_type,
                'open_ended_text_editor' => $open_ended_text_editor]);
        $this->updatePointsBasedOnWeights($assignment);
        $this->addLearningTreeIfBetaAssignment($assignment_question_id, $assignment->id, $question->id);
        $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question->id, 'add');

        return $assignment_question_id;

    }

    public function switchPointsPerQuestion(Assignment $assignment, $total_points)
    {
        switch ($assignment->points_per_question) {
            case('question weight'):
                //switch to points
                DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->update(['weight' => null]);
                break;
            case('number of points'):
                //switch to weights
                $assignment_questions = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->select('id')
                    ->get();
                if (count($assignment_questions)) {
                    DB::table('assignment_question')
                        ->where('assignment_id', $assignment->id)
                        ->update(['weight' => 1, 'points' => $total_points / count($assignment_questions)]);
                }
                break;
        }


    }

    /**
     * @param Assignment $assignment
     * @return array
     */
    public function getQuestionPointsByAssignment(Assignment $assignment): array
    {

        $updated_points_info = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->select('question_id', 'points')
            ->get();

        $formatted_updated_points = [];
        foreach ($updated_points_info as $key => $updated_point) {
            $formatted_updated_points[$key]['question_id'] = $updated_point->question_id;
            $formatted_updated_points[$key]['points'] = Helper::removeZerosAfterDecimal($updated_point->points);
        }
        return $formatted_updated_points;

    }

    /**
     * @param Assignment $assignment
     * @param int $question_id
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param $open_ended_submission_type
     * @param $open_ended_text_editor
     * @param BetaCourseApproval $betaCourseApproval
     * @return void
     * @throws Exception
     */
    public function addQuestionToAssignmentByQuestionId(Assignment             $assignment,
                                                        int                    $question_id,
                                                        AssignmentSyncQuestion $assignmentSyncQuestion,
                                                                               $open_ended_submission_type,
                                                                               $open_ended_text_editor,
                                                        BetaCourseApproval     $betaCourseApproval)
    {

        switch ($assignment->points_per_question) {
            case('number of points'):
                $points = $assignment->default_points_per_question;
                $weight = null;
                break;
            case('question weight'):
                $points = 0;//will be updated below
                $weight = 1;
                break;
            default:
                throw new exception ("Invalid points_per_question");
        }

        DB::table('assignment_question')
            ->insert([
                'assignment_id' => $assignment->id,
                'question_id' => $question_id,
                'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                'points' => $points,
                'weight' => $weight,
                'open_ended_submission_type' => $open_ended_submission_type,
                'completion_scoring_mode' => $assignment->scoring_type === 'c' ? $assignment->default_completion_scoring_mode : null,
                'open_ended_text_editor' => $open_ended_text_editor]);
        $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
        $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question_id, 'add');
    }

    public function updatePointsBasedOnWeights($assignment)
    {
        if ($assignment->points_per_question === 'question weight') {
            $assignment_questions = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->get();
            $weights_total = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->sum('weight');

            foreach ($assignment_questions as $assignment_question) {
                $points = ($assignment_question->weight / $weights_total) * $assignment->total_points;
                DB::table('assignment_question')
                    ->where('id', $assignment_question->id)
                    ->update(['points' => $points]);
            }
        }
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @return bool
     */
    public function questionExistsInOtherAssignments(Assignment $assignment, Question $question)
    {
        return DB::table('assignment_question')
            ->where('assignment_id', '<>', $assignment->id)
            ->where('question_id', $question->id)
            ->get()
            ->isNotEmpty();
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @return bool
     */
    public function questionHasAutoGradedOrFileSubmissionsInOtherAssignments(Assignment $assignment, Question $question): bool
    {
        $auto_graded_submissions = DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', '<>', $assignment->id)
            ->where('question_id', $question->id)
            ->first();
        $submission_files = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', '<>', $assignment->id)
            ->where('question_id', $question->id)
            ->first();
        return $auto_graded_submissions || $submission_files;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @return bool
     */
    public function questionHasAutoGradedOrFileSubmissionsInThisAssignment(Assignment $assignment, Question $question): bool
    {
        $auto_graded_submissions = DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();
        $submission_files = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();
        return $auto_graded_submissions || $submission_files;

    }

    public function addLearningTreeIfBetaAssignment(int $assignment_question_id,
                                                    int $assignment_id,
                                                    int $question_id)
    {
        $beta_learning_tree = DB::table('beta_course_approvals')
            ->where('beta_assignment_id', $assignment_id)
            ->where('beta_question_id', $question_id)
            ->where('beta_learning_tree_id', '<>', 0)
            ->first();
        if ($beta_learning_tree) {
            DB::table('assignment_question_learning_tree')
                ->insert([
                    'assignment_question_id' => $assignment_question_id,
                    'learning_tree_id' => $beta_learning_tree->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
        }

    }

    public function completedAllAssignmentQuestions($assignment)
    {
        $num_technology_questions = $assignment->number_of_randomized_assignments
            ?: DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                ->where('technology', '<>', 'text')
                ->count();
        $num_non_technology_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('open_ended_submission_type', '<>', '0')
            ->get()
            ->count();
        if ($num_technology_questions + $num_non_technology_questions === 0) {
            return false;
        }
        $num_submitted_technology_questions = DB::table('submissions')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->get()
            ->count();
        if ($num_technology_questions !== $num_submitted_technology_questions) {
            return false;
        }
        $num_submitted_non_technology_questions = DB::table('submission_files')
            ->where('assignment_id', $assignment->id)
            ->where('user_id', Auth::user()->id)
            ->where('type', '<>', 'a')
            ->get()
            ->count();
        return $num_submitted_non_technology_questions === $num_non_technology_questions;

    }

    public function importAssignmentQuestionsAndLearningTrees(int $from_assignment_id, int $to_assignment_id)
    {
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $from_assignment_id)
            ->get();
        foreach ($assignment_questions as $key => $assignment_question) {
            $assignment_question->assignment_id = $to_assignment_id;
            //add each question
            $assignment_question_array = json_decode(json_encode($assignment_question), true);
            unset($assignment_question_array['id']);
            $new_assignment_question_id = DB::table('assignment_question')->insertGetId($assignment_question_array);
            //add the learning tree associated with the question
            $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
                ->where('assignment_question_id', $assignment_question->id)
                ->first();
            if ($assignment_question_learning_tree) {
                $new_data['assignment_question_id'] = $new_assignment_question_id;
                $new_data['created_at'] = $new_data['updated_at'] = Carbon::now();
                $fields = ['learning_tree_id',
                    'learning_tree_success_level',
                    'learning_tree_success_criteria',
                    'number_of_successful_branches_for_a_reset',
                    'min_time',
                    'min_number_of_successful_assessments',
                    'free_pass_for_satisfying_learning_tree_criteria'
                ];
                foreach ($fields as $field) {
                    $new_data[$field] = $assignment_question_learning_tree->{$field};
                }
                DB::table('assignment_question_learning_tree')->insert($new_data);
            }
        }
    }

    public function getNewQuestionOrder(Assignment $assignment)
    {
        $max_order = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->max('order');
        return $max_order ? $max_order + 1 : 1;
    }

    public function getQuestionCountByAssignmentIds(Collection $assignments)
    {
        $questions_count_by_assignment_id = [];
        $non_randomized_assignment_ids = [];
        foreach ($assignments as $assignment) {
            if ($assignment->number_of_randomized_assessments) {
                $questions_count_by_assignment_id[$assignment->id] = $assignment->number_of_randomized_assessments;
            } else {
                $non_randomized_assignment_ids[] = $assignment->id;
            }
        }

        $questions_count = DB::table('assignment_question')
            ->whereIn('assignment_id', $non_randomized_assignment_ids)
            ->groupBy('assignment_id')
            ->select(DB::raw('count(*) as num_questions'), 'assignment_id')
            ->get();

        //reogranize by assignment id
        foreach ($questions_count as $key => $value) {
            $questions_count_by_assignment_id[$value->assignment_id] = $value->num_questions;
        }
        return $questions_count_by_assignment_id;
    }

    /**
     * @param $question_info
     * @return string
     */
    public function getFormattedClickerStatus($question_info): string
    {
        $formatted_clicker_status = 'Error with formatted clicker status logic';
        if (!$question_info->clicker_start && !$question_info->clicker_end) {
            $formatted_clicker_status = 'neither_view_nor_submit';
        } else if (time() >= strtotime($question_info->clicker_start) && time() <= strtotime($question_info->clicker_end)) {
            $formatted_clicker_status = 'view_and_submit';
        } else if (time() > strtotime($question_info->clicker_end)) {
            $formatted_clicker_status = 'view_and_not_submit';
        }
        return $formatted_clicker_status;

    }

    public function orderQuestions(array $ordered_questions, Assignment $assignment)
    {
        foreach ($ordered_questions as $key => $question_id) {
            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question_id)
                ->update(['order' => $key + 1]);
        }

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     */
    public
    function updateAssignmentScoreBasedOnRemovedQuestion(Assignment $assignment,
                                                         Question   $question)
    {

        $scores = DB::table('scores')->where('assignment_id', $assignment->id)
            ->select('user_id', 'score')
            ->get();

        $lti_launches_by_user_id = $assignment->ltiLaunchesByUserId();
        $ltiGradePassBack = new LtiGradePassback();

        //just remove the one...
        $submissions = DB::table('submissions')->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->select('user_id', 'score')
            ->get();
        $submissions_by_user_id = [];
        foreach ($submissions as $submission) {
            $submissions_by_user_id[$submission->user_id] = $submission->score;
        }
        $submission_files = DB::table('submission_files')->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('score', '<>', null)
            ->select('user_id', 'score')
            ->get();
        $submission_files_by_user_id = [];
        foreach ($submission_files as $submission_file) {
            $submission_files_by_user_id[$submission_file->user_id] = $submission_file->score;
        }

        foreach ($scores as $score) {
            $submission_file_score = $submission_files_by_user_id[$score->user_id] ?? 0;
            $submission_score = $submissions_by_user_id[$score->user_id] ?? 0;
            $new_score = $score->score - $submission_file_score - $submission_score;
            DB::table('scores')->where('assignment_id', $assignment->id)
                ->where('user_id', $score->user_id)
                ->update(['score' => $new_score]);
            if (isset($lti_launches_by_user_id[$score->user_id])) {
                $ltiGradePassBack->initPassBackByUserIdAndAssignmentId($new_score, $lti_launches_by_user_id[$score->user_id]);
            }
        }

    }
}
