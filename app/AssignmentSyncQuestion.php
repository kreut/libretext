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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AssignmentSyncQuestion extends Model
{
    protected $table = 'assignment_question';

    /**
     * @param $assignment
     * @param $Submission
     * @param $submission
     * @param $question
     * @return bool
     */
    function showRealTimeSolution($assignment, $Submission, $submission, $question): bool
    {
        if (!$submission) {
            return false;
        }
        $real_time_show_solution = false;
        if ($assignment->assessment_type === 'real time'
            && $assignment->scoring_type === 'p'
            && $assignment->solutions_availability === 'automatic') {
            //can view if either they got it right OR they ask to view it (unlimited) OR they
            $attempt = json_decode($submission->submission);
            $proportion_correct = $Submission->getProportionCorrect($question->technology, $attempt);
            $answered_correctly = $question->technology !== 'text' && (abs($proportion_correct - 1) < PHP_FLOAT_EPSILON);
           if (!$answered_correctly) {
                $real_time_show_solution = $assignment->number_of_allowed_attempts === 'unlimited'
                    ? $submission->show_solution
                    : $submission->submission_count >= $assignment->number_of_allowed_attempts;
            } else {
                $real_time_show_solution = true;
            }
        }
        return $real_time_show_solution;
    }


    /**
     * @param Question $question
     * @return array
     */
    public function getAssignmentIdsWithTheQuestion(Question $question): array
    {
        return DB::table('assignment_question')
            ->where('question_id', $question->id)
            ->select('assignment_id')
            ->pluck('assignment_id')
            ->toArray();
    }

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @param string $setting
     * @return mixed
     */
    public function discussItSetting(int $assignment_id, int $question_id, string $setting)
    {
        $assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->first();
        return json_decode($assignment_question->discuss_it_settings)->{$setting};

    }

    public function discussItCompletionStatus(int $user_id, int $assignment_id, int $question_id)
    {
        $discuss_it_settings = json_decode($this->discussItSettings($assignment_id, $question_id));
        $completion_items = ['min_length_of_audio_video',
            'min_number_of_discussion_threads',
            'min_number_of_comments',
            'min_number_of_words'];

        foreach ($completion_items as $key => $completion_item) {
            if (!$discuss_it_settings->{$completion_item}) {
                unset($completion_items[$key]);
            }
        }
        $completion_items = array_values($completion_items);
        $discussion_comments_by_user = DB::table('discussion_comments')->
        join('discussions', 'discussion_comments.dicussion_id', '=', 'discussions.id')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('discussion_comments.user_id', $user_id)
            ->select('discussion_comments.*')
            ->get();
        $total_amount_of_time = 0;
        $total_number_of_discussion_threads = 0;
        $total_number_of_comments = 0;
        $total_number_of_words = 0;
        $completion_status = [];
        foreach ($completion_items as $completion_item) {
            $completion_status[] = ['key' => $completion_item, 'completed' => false];
        }
        if ($discussion_comments_by_user) {
            foreach ($discussion_comments_by_user as $discussion_comment) {
                foreach ($completion_items as $completion_item) {
                    switch ($completion_item) {
                        case('min_length_of_audio_video'):
                            break;
                        case('min_number_of_discussion_threads'):

                            break;
                        case('min_number_of_comments');
                            break;
                        case('min_number_of_words'):

                            break;
                        default:
                            throw new Exception ("No logic yet for  $completion_item.");

                    }

                }
            }

        }

    }

    /**
     * @param int $assignment_id
     * @param int $question_id
     * @return false|string
     */
    public function discussItSettings(int $assignment_id, int $question_id)
    {
        $assignment_question_info = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->select('discuss_it_settings')
            ->first();

        if ($assignment_question_info->discuss_it_settings) {
            $discuss_it_settings = json_decode($assignment_question_info->discuss_it_settings);
        } else {
            $question = Question::find($question_id);
            $assignment = Assignment::find($assignment_id);
            $discuss_it_settings = json_decode($question->getDefaultDiscussItSettings($assignment));
            AssignmentSyncQuestion::where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['discuss_it_settings' => $question->getDefaultDiscussItSettings($assignment)]);
        }

        if ($discuss_it_settings->min_length_of_audio_video) {
            $base = Carbon::now();
            $min_length_of_audio_video = $discuss_it_settings->min_length_of_audio_video;
            $min_length_of_audio_video = str_replace('and', ',', $min_length_of_audio_video);
            $parsedTime = Carbon::parse($min_length_of_audio_video);
            $discuss_it_settings->min_length_of_audio_video_in_milliseconds = $base->diffInMilliseconds($parsedTime);
        }
        return json_encode($discuss_it_settings);

    }


    public function rubricCategoriesByAssignmentAndQuestion(Assignment $assignment, Question $question)
    {
        $question_revision_id = AssignmentSyncQuestion::where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first()
            ->question_revision_id;
        $question_revision_id = $question_revision_id ?: 0;
        $rubric_categories = $question->rubricCategories(new RubricCategory(), $question_revision_id);
        $rubric_category_custom_criteria_by_id = [];

        $rubric_category_custom_criteria = DB::table('rubric_category_custom_criteria')
            ->where('assignment_id', $assignment->id)
            ->get();
        foreach ($rubric_category_custom_criteria as $value) {
            $rubric_category_custom_criteria_by_id[$value->rubric_category_id] = $value->custom_criteria;
        }

        foreach ($rubric_categories as $rubricCategory) {
            if (isset($rubric_category_custom_criteria_by_id[$rubricCategory->id])) {
                $rubricCategory['criteria'] = $rubric_category_custom_criteria_by_id[$rubricCategory->id];
            }
        }
        return $rubric_categories;
    }

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
        $assignment_question_data = [
            'assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'question_revision_id' => $question->latestQuestionRevision('id'),
            'order' => $this->getNewQuestionOrder($assignment),
            'points' => $points, //don't need to test since tested already when creating an assignment
            'weight' => $assignment->points_per_question === 'number of points' ? null : 1,
            'completion_scoring_mode' => $assignment->scoring_type === 'c' ? $assignment->default_completion_scoring_mode : null,
            'open_ended_submission_type' => $question->isDiscussIt() ? 0 : $open_ended_submission_type,
            'discuss_it_settings' => $question->getDefaultDiscussItSettings($assignment),
            'open_ended_text_editor' => $open_ended_text_editor];

        $assignment_question_id = DB::table('assignment_question')
            ->insertGetId($assignment_question_data);
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

        $question_revision_id = Question::find($question_id)->latestQuestionRevision('id');
        $question = Question::find($question_id);
        DB::table('assignment_question')
            ->insert([
                'assignment_id' => $assignment->id,
                'question_id' => $question_id,
                'order' => $assignmentSyncQuestion->getNewQuestionOrder($assignment),
                'points' => $points,
                'weight' => $weight,
                'question_revision_id' => $question_revision_id,
                'open_ended_submission_type' => $question->isDiscussIt() ? 0 : $open_ended_submission_type,
                'discuss_it_settings' => $question->getDefaultDiscussItSettings($assignment),
                'completion_scoring_mode' => $assignment->scoring_type === 'c' ? $assignment->default_completion_scoring_mode : null,
                'open_ended_text_editor' => $open_ended_text_editor]);
        $assignmentSyncQuestion->updatePointsBasedOnWeights($assignment);
        $betaCourseApproval->updateBetaCourseApprovalsForQuestion($assignment, $question_id, 'add');
    }

    /**
     * @param $assignment
     * @return void
     * @throws Exception
     */
    public function updatePointsBasedOnWeights($assignment)
    {
        $assignment_questions = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->get();
        $total_points = 0;
        if ($assignment->number_of_randomized_assessments) {
            $total_points = $assignment->default_points_per_question * $assignment->number_of_randomized_assessments;
        } else {
            if ($assignment->points_per_question === 'question weight') {

                $weights_total = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->sum('weight');

                foreach ($assignment_questions as $assignment_question) {
                    $points = ($assignment_question->weight / $weights_total) * $assignment->total_points;
                    $total_points += $points;
                    DB::table('assignment_question')
                        ->where('id', $assignment_question->id)
                        ->update(['points' => $points]);
                }
            } else {
                foreach ($assignment_questions as $assignment_question) {
                    $total_points += $assignment_question->points;
                }
            }
        }
        if ($assignment->course->lms_course_id && !$assignment->formative) {
            $lmsApi = new LmsAPI();
            $lms_result = $lmsApi->updateAssignment(
                $assignment->course->getLtiRegistration(),
                $assignment->course->user_id,
                $assignment->course->lms_course_id,
                $assignment->lms_assignment_id,
                ['total_points' => $total_points]);
            if ($lms_result['type'] === 'error') {
                throw new Exception("Error updating assignment $assignment->id on  LMS: " . $lms_result['message']);
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
    public function questionHasSomeTypeOfRealStudentSubmission(Assignment $assignment, Question $question): bool
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
        $rubric_category_submissions = DB::table('rubric_category_submissions')
            ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
            ->join('users', 'rubric_category_submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first();

        return $rubric_category_submissions || $auto_graded_submissions || $submission_files;

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @return array
     */
    public function studentEmailsAssociatedWithSomeTypeOfStudentSubmission(Assignment $assignment, Question $question): array
    {
        $auto_graded_submission_emails = DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->select('email')
            ->get()
            ->pluck('email')
            ->toArray();
        $submission_file_emails = DB::table('submission_files')
            ->join('users', 'submission_files.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->select('email')
            ->get()
            ->pluck('email')
            ->toArray();
        $rubric_category_submissions = DB::table('rubric_category_submissions')
            ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
            ->join('users', 'rubric_category_submissions.user_id', '=', 'users.id')
            ->where('fake_student', 0)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->select('email')
            ->get()
            ->pluck('email')
            ->toArray();
        $combined = array_merge($rubric_category_submissions, $auto_graded_submission_emails, $submission_file_emails);
        return array_unique($combined);
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

        $question_ids = [];
        foreach ($assignment_questions as $assignment_question) {
            $question_ids[] = $assignment_question->question_id;
        }
        $question_revision_ids_by_question_ids = $this->getLatestQuestionRevisionsByAssignment($question_ids);
        foreach ($assignment_questions as $assignment_question) {
            $assignment_question->assignment_id = $to_assignment_id;
            $assignment_question->question_revision_id = $question_revision_ids_by_question_ids[$assignment_question->question_id] ?? null;
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
                    'number_of_successful_paths_for_a_reset'
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

    /**
     * @param Collection $assignments
     * @return array
     */
    public function getQuestionCountByAssignmentIds(Collection $assignments): array
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
     * @param int $solutions_released
     * @param $question_info
     * @return string
     */
    public function getFormattedClickerStatus(int $solutions_released, $question_info): string
    {
        $formatted_clicker_status = 'Error with formatted clicker status logic';
        if (auth()->user()->role === 2) {
            if (!$question_info->clicker_start && !$question_info->clicker_end) {
                $formatted_clicker_status = 'show_go';
            } else if (time() >= strtotime($question_info->clicker_start) && time() <= strtotime($question_info->clicker_end)) {
                $formatted_clicker_status = 'view_and_submit';
            } else if (time() > strtotime($question_info->clicker_end)) {
                $formatted_clicker_status = 'view_and_not_submit';
            }
        } else {
            if ($solutions_released) {
                $formatted_clicker_status = 'view_and_not_submit';
            } else {
                if (!$question_info->clicker_start && !$question_info->clicker_end) {
                    $formatted_clicker_status = 'neither_view_nor_submit';
                } else if (time() >= strtotime($question_info->clicker_start) && time() <= strtotime($question_info->clicker_end)) {
                    $formatted_clicker_status = 'view_and_submit';
                } else if (time() > strtotime($question_info->clicker_end)) {
                    $formatted_clicker_status = 'view_and_not_submit';
                }
            }
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

        $rubric_category_submissions = DB::table('rubric_category_submissions')
            ->join('rubric_categories', 'rubric_category_submissions.rubric_category_id', '=', 'rubric_categories.id')
            ->where('question_id', $question->id)
            ->where('assignment_id', $assignment->id)
            ->where('custom_score', '<>', null)
            ->select('user_id', 'custom_score')
            ->get();
        $rubric_category_submissions_by_user_id = [];
        foreach ($rubric_category_submissions as $rubric_category_submission) {
            $rubric_category_submissions_by_user_id[$rubric_category_submission->user_id] = $rubric_category_submission->custom_score;
        }

        foreach ($scores as $score) {
            $submission_file_score = $submission_files_by_user_id[$score->user_id] ?? 0;
            $submission_score = $submissions_by_user_id[$score->user_id] ?? 0;
            $rubric_category_submission_score = $rubric_category_submissions_by_user_id[$score->user_id] ?? 0;

            $new_score = $score->score - $submission_file_score - $submission_score - $rubric_category_submission_score;
            DB::table('scores')->where('assignment_id', $assignment->id)
                ->where('user_id', $score->user_id)
                ->update(['score' => $new_score]);
            if (isset($lti_launches_by_user_id[$score->user_id])) {
                DB::table('lti_grade_passbacks')
                    ->where('user_id', $score->user_id)
                    ->where('assignment_id', $assignment->id)
                    ->update(['score' => $new_score, 'updated_at' => now()]);
            }
        }
        DB::table('passback_by_assignments')->insert(
            ['assignment_id' => $assignment->id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()]);

    }

    /**
     * @param array $question_ids
     * @return array
     */
    public function getLatestQuestionRevisionsByAssignment(array $question_ids): array
    {

        $latest_question_revisions = DB::table('question_revisions')
            ->whereIn('question_id', $question_ids)
            ->orderBy('revision_number', 'desc')
            ->select('id', 'question_id')
            ->get();
        $latest_question_revisions_by_id = [];
        foreach ($latest_question_revisions as $latest_question_revision) {
            if (!isset($latest_question_revisions_by_id[$latest_question_revision->question_id])) {
                $latest_question_revisions_by_id[$latest_question_revision->question_id] = $latest_question_revision->id;
            }
        }
        return $latest_question_revisions_by_id;


    }
}
