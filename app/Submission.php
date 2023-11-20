<?php

namespace App;

use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Traits\JWT;
use \Exception;

use App\Http\Requests\StoreSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\Request;
use stdClass;

class Submission extends Model
{

    use DateFormatter;
    use JWT;

    protected $guarded = [];

    public function updateScoresWithNewTotalWeight($assignment_id, $old_total_points, $new_total_points)
    {
        $factor = $new_total_points / $old_total_points;
        $submissions = $this->where('assignment_id', $assignment_id)->get();
        foreach ($submissions as $submission) {
            $submission->update(['score' => $factor * $submission->score]);
        }
    }

    /**
     * @throws Exception
     */
    public function getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question): array
    {
        $submissions = $assignment->submissions->where('question_id', $question->id);

        $submissions_by_user = [];

        foreach ($submissions as $submission) {
            $submissions_by_user[$submission->user_id] = $submission;
        }


        $auto_graded_submission_info_by_user = [];

        foreach ($enrolled_users as $enrolled_user) {
            if (isset($submissions_by_user[$enrolled_user->id])) {
                $submission = $submissions_by_user[$enrolled_user->id];
                $auto_graded_submission_info_by_user[] = [
                    'user_id' => $enrolled_user->id,
                    'question_id' => $question->id,
                    'name' => $enrolled_user->first_name . ' ' . $enrolled_user->last_name,
                    'email' => $enrolled_user->email,
                    'submission' => $this->getStudentResponse($submission, $question->technology),
                    'submission_count' => $submission->submission_count,
                    'score' => Helper::removeZerosAfterDecimal($submission->score),
                    'updated_at' => $submission->updated_at
                ];
            }

        }
        if ($auto_graded_submission_info_by_user) {
            usort($auto_graded_submission_info_by_user, function ($a, $b) {
                return $a['name'] <=> $b['name'];
            });
        }
        return $auto_graded_submission_info_by_user;
    }

    /**
     * @throws Exception
     */
    function getProportionCorrect(string $technology, $submission)
    {

        switch ($technology) {
            case('h5p'):
                $proportion_correct = (floatval($submission->result->score->raw) / floatval($submission->result->score->max));
                break;
            case('imathas'):
                $proportion_correct = floatval($submission->score);
                break;
            case('webwork'):
                $score = (object)$submission->score;
                if (!isset($score->result)) {
                    throw new Exception ('Please refresh the page and resubmit to use the upgraded Webwork renderer.');
                }
                $weighted_score = 0;
                $num_answers = 0;
                foreach ($score->answers as $value) {
                    $num_answers++;
                }
                ///if there's an issue with a weighted question, use: install_weighted_grader()
                foreach ($score->answers as $answer) {
                    $answer = (object)$answer;
                    if (isset($answer->score)) {
                        $weighted_score += isset($answer->weight) ? $answer->score * $answer->weight / 100 : $answer->score / $num_answers;
                    }
                }
                $proportion_correct = floatval($weighted_score);

                break;
            case('qti'):
                $question_type = $submission->question->questionType;
                switch ($question_type) {
                    case('highlight_text'):
                        $student_responses = json_decode($submission->student_response);
                        $score = 0;
                        $num_correct_answers = 0;
                        foreach ($submission->question->responses as $response) {
                            $num_correct_answers += +$response->correctResponse;
                            $score = $score + $this->computeScoreFromPlusMinusScoring($response, $student_responses);
                        }
                        $proportion_correct = Max($score, 0) / $num_correct_answers;
                        break;
                    case('drag_and_drop_cloze'):
                        $student_responses = json_decode($submission->student_response);
                        $score = 0;
                        $correct_responses = [];
                        foreach ($submission->question->correctResponses as $response) {
                            $correct_responses[] = $response->identifier;
                        }
                        foreach ($student_responses as $response) {
                            $score += in_array($response, $correct_responses);
                        }
                        $proportion_correct = $score / count($student_responses);
                        break;
                    case('matrix_multiple_choice'):
                        $student_responses = json_decode($submission->student_response);
                        $message = '';
                        foreach ($student_responses as $key => $student_response) {
                            $row_num = $key + 1;
                            if ($student_response === null) {
                                $message .= "Row $row_num of the table does not have a selected response.<br>";
                            }
                        }
                        if ($message) {
                            $response['message'] = $message;
                            $response['type'] = 'error';
                            $this->_returnJsonAndExit($response);
                        }

                        $score = 0;
                        $num_rows = count($submission->question->rows);
                        foreach ($submission->question->rows as $key => $row) {
                            $score += +($student_responses[$key] === $row->correctResponse);
                        }
                        $proportion_correct = $score / $num_rows;
                        break;
                    case('drop_down_table'):
                        $student_responses = json_decode($submission->student_response);
                        $number_to_select = count($submission->question->rows);
                        if (count($student_responses) !== $number_to_select) {
                            $response['message'] = "Please make a selection from each row before submitting.";
                            $response['type'] = 'error';
                            $this->_returnJsonAndExit($response);
                        }

                        $score = 0;
                        $num_rows = count($submission->question->rows);
                        foreach ($submission->question->rows as $row) {
                            foreach ($row->responses as $response) {
                                if ($response->correctResponse && in_array($response->identifier, $student_responses)) {
                                    $score++;
                                }
                            }
                        }
                        $proportion_correct = $score / $num_rows;
                        break;
                    case('highlight_table'):
                    case('multiple_response_grouping'):
                        $student_responses = json_decode($submission->student_response);
                        $score = 0;
                        $num_correct_answers = 0;
                        foreach ($submission->question->rows as $row) {
                            foreach ($row->responses as $response) {
                                $num_correct_answers += +$response->correctResponse;
                                $score = $score + $this->computeScoreFromPlusMinusScoring($response, $student_responses);
                            }
                        }

                        $proportion_correct = Max($score, 0) / $num_correct_answers;
                        break;
                    case('multiple_response_select_n'):
                    case('multiple_response_select_all_that_apply'):
                    {
                        $penalty = 0;
                        $student_responses = json_decode($submission->student_response);
                        if ($question_type === 'multiple_response_select_all_that_apply') {
                            if (!count($student_responses)) {
                                $response['message'] = "Please select at least one of the responses.";
                                $response['type'] = 'error';
                                $this->_returnJsonAndExit($response);
                            }
                            $penalty = -1;
                        }
                        if ($question_type === 'multiple_response_select_n') {
                            $number_to_select = +$submission->question->numberToSelect;
                            if (count($student_responses) !== $number_to_select) {
                                $response['message'] = "Please check $number_to_select boxes before submitting.";
                                $response['type'] = 'error';
                                $this->_returnJsonAndExit($response);
                            }
                        }
                        $correct_responses = [];
                        foreach ($submission->question->responses as $response) {
                            if ($response->correctResponse) {
                                $correct_responses[] = $response->identifier;
                            }
                        }
                        $score = 0;
                        foreach ($student_responses as $response) {
                            $change = in_array($response, $correct_responses) ? 1 : $penalty;
                            $score += $change;
                        }
                        $score = max($score, 0);
                        $proportion_correct = $score / count($correct_responses);
                        break;
                    }
                    case('bow_tie'):
                    {
                        $student_response = json_decode($submission->student_response);
                        $num_correct = 0;
                        foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $group) {
                            foreach ($submission->question->{$group} as $item) {
                                if ($item->correctResponse && in_array($item->identifier, $student_response->{$group})) {
                                    $num_correct++;
                                }
                            }
                        }
                        $proportion_correct = $num_correct / 5;
                        break;
                    }
                    case('numerical'):
                        $student_response = $submission->student_response;
                        $margin_of_error = (float)$submission->question->correctResponse->marginOfError;
                        $diff = abs((float)$student_response - (float)$submission->question->correctResponse->value);
                        $proportion_correct = +($diff <= $margin_of_error);
                        break;
                    case('matching'):
                        $student_response = json_decode($submission->student_response);
                        $student_response_by_term_identifier = [];
                        $chosen_match_identifiers = [];
                        foreach ($student_response as $value) {
                            $student_response_by_term_identifier[$value->identifier] = $value->chosenMatchIdentifier;
                        }
                        $terms_to_match = $submission->question->termsToMatch;
                        $num_matches = count($terms_to_match);
                        $num_correct = 0;
                        foreach ($terms_to_match as $term_to_match) {
                            if (isset($student_response_by_term_identifier[$term_to_match->identifier])) {
                                if ($student_response_by_term_identifier[$term_to_match->identifier] === $term_to_match->matchingTermIdentifier) {
                                    $num_correct++;
                                }
                            } else {
                                $response['message'] = "Please choose a matching term for all terms to match.";
                                if (app()->environment('testing')) {
                                    throw new Exception ($response['message']);
                                }
                                $this->_returnJsonAndExit($response);
                            }
                        }

                        foreach ($student_response as $value) {
                            if (in_array($value->chosenMatchIdentifier, $chosen_match_identifiers)) {
                                $response['message'] = "Each matching term should be chosen only once.";
                                if (app()->environment('testing')) {
                                    throw new Exception ($response['message']);
                                }
                                $this->_returnJsonAndExit($response);
                            }
                            $chosen_match_identifiers[] = $value->chosenMatchIdentifier;
                        }

                        $proportion_correct = $num_correct / $num_matches;
                        break;
                    case('multiple_choice'):
                    case('true_false'):
                        $simpleChoices = $submission->question->simpleChoice;
                        if (!$submission->student_response) {
                            $response['message'] = "Please make a selection before submitting.";
                            $this->_returnJsonAndExit($response);
                        }
                        $proportion_correct = floatval(0);
                        foreach ($simpleChoices as $choice) {
                            if ($submission->student_response === $choice->identifier
                                && property_exists($choice, 'correctResponse')
                                && $choice->correctResponse) {
                                $proportion_correct = floatval(1);
                            }
                        }

                        break;
                    case('matrix_multiple_response'):
                        $student_response = json_decode($submission->student_response);
                        $col_headers = $submission->question->colHeaders;
                        array_shift($col_headers); //first column isn't for the checkboxes
                        $rows = $submission->question->rows;
                        $unsubmitted_cols = [];
                        foreach ($col_headers as $col_key => $col) {
                            $submitted_at_least_one = false;
                            foreach ($rows as $row) {
                                $row_response_by_col = $row->responses[$col_key];
                                if (in_array($row_response_by_col->identifier, $student_response)) {
                                    $submitted_at_least_one = true;
                                }
                            }
                            if (!$submitted_at_least_one) {
                                $unsubmitted_cols[] = $col;
                            }
                        }

                        if ($unsubmitted_cols) {
                            $cols_to_fix = implode(', ', $unsubmitted_cols);

                            $plural = count($unsubmitted_cols) > 1 ? 's' : '';
                            $response['message'] = "You have not made any selections for the following column$plural: $cols_to_fix.";
                            $this->_returnJsonAndExit($response);
                        }
                        $total_responses = count($rows) * count($col_headers);
                        $num_correct = 0;
                        foreach ($rows as $row) {
                            foreach ($row->responses as $response) {
                                $correct = ($response->correctResponse && in_array($response->identifier, $student_response))
                                    || (!$response->correctResponse && !in_array($response->identifier, $student_response));
                                $num_correct += $correct ? 1 : -1;
                            }

                        }
                        $proportion_correct = max(floatval($num_correct / $total_responses), 0);
                        break;
                    case
                    ('multiple_answers'):
                        $student_response = json_decode($submission->student_response);
                        if (!$student_response) {
                            $response['message'] = "Please make at least one selection before submitting.";
                            $response['type'] = 'error';
                            $this->_returnJsonAndExit($response);

                        }
                        $simpleChoices = $submission->question->simpleChoice;
                        $num_answers = count($simpleChoices);
                        $num_correct = 0;
                        foreach ($simpleChoices as $choice) {
                            $correct = ($choice->correctResponse && in_array($choice->identifier, $student_response))
                                || (!$choice->correctResponse && !in_array($choice->identifier, $student_response));
                            $num_correct += (int)$correct;
                        }
                        $proportion_correct = floatval($num_correct / $num_answers);
                        break;
                    case('drop_down_rationale_triad'):
                        $student_responses = json_decode($submission->student_response);
                        $responses_by_type['condition'] = $responses_by_type['rationales'] = [];
                        foreach ($student_responses as $response) {
                            $key = $response->identifier === 'condition' ? 'condition' : 'rationales';
                            $responses_by_type[$key][] = $response->value;
                        }
                        if ($responses_by_type['rationales'][0] === $responses_by_type['rationales'][1]) {
                            $result['message'] = "You have chosen the same rationale twice.";
                            $result['type'] = 'error';
                            if (app()->environment() === 'testing') {
                                throw new Exception($result['message']);
                            }
                            $this->_returnJsonAndExit($result);
                        }
                        $num_points = 0;
                        $cause_is_correct = false;
                        foreach ($submission->question->inline_choice_interactions->condition as $condition) {
                            if ($condition->correctResponse && in_array($condition->value, $responses_by_type['condition'])) {
                                $cause_is_correct = true;
                            }
                        }

                        if ($cause_is_correct) {
                            $num_points = 1;
                            $num_correct_rationales = 0;
                            foreach ($submission->question->inline_choice_interactions->rationales as $rationale) {
                                if ($rationale->correctResponse && in_array($rationale->value, $responses_by_type['rationales'])) {
                                    $num_correct_rationales++;
                                }
                            }
                            if ($num_correct_rationales === 2) {
                                $num_points = 2;
                            }
                        }
                        $proportion_correct = $num_points / 2;
                        break;

                    case('drop_down_rationale'):
                    case('select_choice'):
                        preg_match_all('/\[(.*?)\]/', $submission->question->itemBody, $matches);
                        $identifiers = $matches[1];
                        $student_responses = json_decode($submission->student_response);
                        $num_identifiers = count($identifiers);
                        $num_correct = 0;
                        foreach ($identifiers as $key => $identifier) {
                            $student_response = $student_responses[$key]->value;
                            $identifier_choices = $submission->question->inline_choice_interactions->{$identifier};
                            foreach ($identifier_choices as $choice) {
                                if ($choice->value === $student_response && $choice->correctResponse) {
                                    $num_correct++;
                                }
                            }
                        }
                        if ($question_type === 'select_choice') {
                            $proportion_correct = floatval($num_correct / $num_identifiers);
                        }

                        if ($question_type === 'drop_down_rationale') {
                            switch ($submission->question->dropDownRationaleType) {
                                case('dyad'):
                                    $proportion_correct = $num_correct === 2 ? 1 : 0;
                                    break;
                                default;
                                    throw new Exception("$submission->question->dropDownRationaleType is not a valid drop down rationale type.");
                            }
                        }
                        break;
                    case('fill_in_the_blank'):
                        $correct_responses = $submission->question->responseDeclaration->correctResponse;
                        $student_responses = json_decode($submission->student_response);
                        $num_fill_in_the_blanks = count($correct_responses);
                        $num_correct = 0;
                        foreach ($correct_responses as $key => $correct_response) {
                            if ($this->correctFillInTheBlank($correct_response, $student_responses[$key]->value))
                                $num_correct++;
                        }
                        $proportion_correct = floatval($num_correct / $num_fill_in_the_blanks);

                        break;
                    default:
                        throw new Exception("$question_type is not yet available for scoring.");

                }
                break;
            default:
                $proportion_correct = 0;
        }

        return $proportion_correct;
    }

    /**
     * @throws Exception
     */
    public
    function correctFillInTheBlank(object $correct_response, string $student_response): bool
    {
        $value = trim($correct_response->value);
        $student_response = trim($student_response);

        switch ($correct_response->matchingType) {
            case('exact'):
                $correct = $correct_response->caseSensitive === 'yes'
                    ? $value === $student_response
                    : strtolower($value) === strtolower($student_response);
                break;
            case('substring'):
                $correct = $correct_response->caseSensitive === 'yes'
                    ? strpos($value, $student_response) !== false
                    : stripos($value, $student_response) !== false;
                break;
            default:
                throw new Exception("$correct_response->matching_type is not a valid matching type.");
        }
        return $correct;

    }

    /**
     * @param StoreSubmission $request
     * @param Submission $submission
     * @param Assignment $Assignment
     * @param Score $score
     * @param DataShop $dataShop
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public
    function store(StoreSubmission        $request,
                   Submission             $submission,
                   Assignment             $Assignment,
                   Score                  $score,
                   DataShop               $dataShop,
                   AssignmentSyncQuestion $assignmentSyncQuestion): array
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message
        // $data = $request->validated();//TODO: validate here!!!!!
        // $data = $request->all(); ///maybe request->all() flag in the model or let it equal request???
        // Log::info(print_r($request->all(), true));


        $data = $request;

        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);


        if ($assignment->course->anonymous_users && (Helper::isAnonymousUser() || Helper::hasAnonymousUserSession())) {
            $response['type'] = 'success';
            return $response;
        }

        $assignment_question = DB::table('assignment_question')
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $data['question_id'])
            ->select('id',
                'points',
                'question_id',
                'assignment_id',
                'completion_scoring_mode',
                'open_ended_submission_type')
            ->first();

        if (!$assignment_question) {
            $response['message'] = 'That question is not part of the assignment.';
            return $response;
        }


        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);

        $questionLevelOverride = new QuestionLevelOverride();
        $assignmentLevelOverride = new AssignmentLevelOverride();
        $has_question_level_override = $questionLevelOverride->hasAutoGradedOverride($assignment->id, $data['question_id'], $assignmentLevelOverride);
        if (!$authorized->allowed()) {

            if (!$has_question_level_override) {
                $response['message'] = $authorized->message();
                return $response;
            }
        }
        switch ($data['technology']) {
            case('h5p'):
                $submission = json_decode($data['submission']);
                //hotspots don't have anything
                $no_submission = isset($submission->result->response) && str_replace('[,]', '', $submission->result->response) === '';
                if ($no_submission) {
                    $response['type'] = 'info';
                    $response['message'] = $response['not_updated_message'] = "It looks like you submitted a blank response.  Please make a selection before submitting.";
                    return $response;
                }
                if ($data['is_h5p_video_interaction']) {
                    $url_components = parse_url($submission->object->id);
                    parse_str($url_components['query'], $params);
                    if (isset($params['subContentId'])) {
                        return $this->processH5PVideoInteraction($assignment_question, $submission, $data, $assignment, $score, $assignmentSyncQuestion, $dataShop, $params['subContentId']);
                    }
                }

                $proportion_correct = $this->getProportionCorrect('h5p', $submission);
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                break;
            case('imathas'):
                $submission = $data['submission'];
                $proportion_correct = $this->getProportionCorrect('imathas', $submission);
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                $data['submission'] = json_encode($data['submission'], JSON_UNESCAPED_SLASHES);
                break;
            case('webwork'):
                // Log::info('case webwork');
                $submission = $data['submission'];
                //json_encode($submission, )
                #Log::info('Submission:' . json_encode($submission));
                #Log::info('Submission Score:' . json_encode($submission->score));
                //$submission_score = json_decode(json_encode($submission->score));
                $proportion_correct = $this->getProportionCorrect('webwork', (object)$submission);//
                //Log::info( $submission_score->result);
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                $data['submission'] = json_encode($data['submission']);
                break;
            case('qti'):
                $question = DB::table('questions')
                    ->where('id', $data['question_id'])->first();
                if (!$question) {
                    $response['message'] = "{$data['question_id']} does not exist in the database.";
                    return $response;
                }
                $submission = new stdClass();
                $submission->question = json_decode($question->qti_json);
                $submission->student_response = $data['submission'];
                $proportion_correct = $this->getProportionCorrect('qti', $submission);
                $submission->proportion_correct = $proportion_correct;
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                $data['submission'] = json_encode($submission);
                break;
            default:
                throw new Exception("{$data['technology']} is not a valid technology.");
        }


        $data['all_correct'] = $data['score'] >= floatval($assignment_question->points);//>= so I don't worry about decimals

        try {
            //do the extension stuff also
            $submission = Submission::where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->first();

            $learning_tree = DB::table('assignment_question')
                ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
                ->join('learning_trees', 'assignment_question_learning_tree.learning_tree_id', '=', 'learning_trees.id')
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->select('learning_tree')
                ->get();
            $message = 'Auto-graded submission saved.';
            $hint_penalty = in_array($assignment->assessment_type, ['real time', 'learning tree'])
                ? $this->getHintPenalty($data['user_id'], $assignment, $data['question_id'])
                : 0;

            if ($assignment->assessment_type === 'learning tree') {
                $assignment_question_learning_tree = DB::table('assignment_question')
                    ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
                    ->where('assignment_id', $data['assignment_id'])
                    ->where('question_id', $data['question_id'])
                    ->select('number_of_successful_paths_for_a_reset', 'learning_tree_id')
                    ->first();
                $learningTree = LearningTree::find($assignment_question_learning_tree->learning_tree_id);
            }
            if ($submission) {
                if (in_array($assignment->assessment_type, ['real time', 'learning tree'])) {
                    $too_many_submissions = $this->tooManySubmissions($assignment, $submission);
                    if ($too_many_submissions) {
                        $plural = $assignment->number_of_allowed_attempts === '1' ? '' : 's';
                        $message = "You are only allowed $assignment->number_of_allowed_attempts attempt$plural.  ";
                        $response['message'] = $message;
                        if ($assignment->assessment_type === 'learning tree') {
                            $response['learning_tree_message'] = true;
                            $response['message'] = $message;
                            $response['type'] = 'info';
                        }
                        return $response;
                    }
                    $num_deductions_to_apply = $submission->submission_count;
                    $proportion_of_score_received = 1 - (($num_deductions_to_apply * $assignment->number_of_allowed_attempts_penalty + $hint_penalty) / 100);
                    // Log::info($submission->score . ' ' . $data['score'] . ' ' . $num_deductions_to_apply . ' ' . $assignment->number_of_allowed_attempts_penalty . ' ' . $hint_penalty . ' ' . $proportion_of_score_received);

                    if (request()->user()->role === 3) {
                        $data['score'] = max($data['score'] * $proportion_of_score_received, 0);
                        if ($data['score'] < $submission->score) {
                            $response['type'] = 'error';
                            $response['message'] = $proportion_of_score_received < 1
                                ? "With the number of attempts and hint penalty applied, submitting will give you a lower score on this question than you currently have, so the submission will not be accepted."
                                : "This attempt would give you less points than you currently have so it will not be accepted.";
                            return $response;
                        }
                    }
                }
                if (request()->user()->role === 3) {
                    if ($this->latePenaltyPercent($assignment, Carbon::now('UTC'))) {
                        $score_with_late_penalty = $this->applyLatePenalyToScore($assignment, $data['score']);
                        if ($score_with_late_penalty < $submission->score) {
                            $response['type'] = 'error';
                            $response['message'] = "With the late deduction, submitting will give you a lower score on this question than you currently have so the submission will not be accepted.";
                            return $response;
                        }
                    }
                }
                DB::beginTransaction();
                $submission->submission = $data['submission'];
                $submission->answered_correctly_at_least_once = $data['all_correct'];
                $submission->score = request()->user()->role === 3 ? $this->applyLatePenalyToScore($assignment, $data['score']) : $data['score'];
                $submission->submission_count = $submission->submission_count + 1;
                $submission->save();

            } else {
                $proportion_of_score_received = 1 - ($hint_penalty / 100);
                $data['score'] = $data['score'] * $proportion_of_score_received;
                if ($assignment->assessment_type === 'learning tree') {
                    $assignment_question_learning_tree = DB::table('assignment_question')
                        ->join('assignment_question_learning_tree', 'assignment_question.id', '=', 'assignment_question_learning_tree.assignment_question_id')
                        ->where('assignment_id', $data['assignment_id'])
                        ->where('question_id', $data['question_id'])
                        ->select('number_of_successful_paths_for_a_reset', 'learning_tree_id')
                        ->first();

                    $number_of_successful_paths_for_a_reset = $assignment_question_learning_tree->number_of_successful_paths_for_a_reset;
                    $learningTree = LearningTree::find($assignment_question_learning_tree->learning_tree_id);
                    if (!$data['all_correct']) {
                        $number_of_learning_tree_paths = count($learningTree->finalQuestionIds());

                        $learningTreeReset = LearningTreeReset::where('user_id', $data['user_id'])
                            ->where('assignment_id', $assignment->id)
                            ->where('learning_tree_id', $assignment_question_learning_tree->learning_tree_id)
                            ->first();
                        $number_resets_available = $learningTreeReset ? $learningTreeReset->number_resets_available : 0;
                        $resets_available = $number_of_learning_tree_paths - $number_resets_available >= $number_of_successful_paths_for_a_reset;


                        $plural = $number_of_successful_paths_for_a_reset > 1 ? 's' : '';
                        $message = "Unfortunately, you did not answer this question correctly.  ";
                        $message .= $resets_available
                            ? "Explore the Learning Tree and complete $number_of_successful_paths_for_a_reset path$plural for a reset."
                            : "You can explore the tree but there are not enough paths remaining for you to earn a reset of your original submission.";

                    }
                }
                DB::beginTransaction();
                $submission = Submission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'score' => $this->applyLatePenalyToScore($assignment, $data['score']),
                    'answered_correctly_at_least_once' => $data['all_correct'],
                    'submission_count' => 1]);
            }
            //update the score if it's supposed to be updated
            if ($assignment->assessment_type === 'learning tree'
                && (!request()->user()->fake_student || app()->environment() === 'local')) {
                $assignment_course_info = $assignment->assignmentCourseInfo();
                $learning_tree_analytics_data = [
                    'course_name'=>  $assignment_course_info->course_name,
                    'assignment_name'=>  $assignment_course_info->assignment_name,
                    'instructor'=>  $assignment_course_info->instructor,
                    'user_id' => request()->user()->id,
                    'learning_tree_id' => $learningTree->id,
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'root_node' => 1,
                    'action' => 'submit',
                    'response' => $data['all_correct'] ? 'success' : 'failure'
                ];
                LearningTreeAnalytics::create($learning_tree_analytics_data);
            }
            $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->lms_grade_passback === 'automatic');
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            UnconfirmedSubmission::where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->delete();
            $score_not_updated = ($learning_tree->isNotEmpty() && !$data['all_correct']);
            if (\App::runningUnitTests()) {
                $response['submission_id'] = $submission->id;
            }
            $response['type'] = $score_not_updated ? 'info' : 'success';
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            $response['message'] = $message;
            $response['learning_tree_message'] = !$data['all_correct'];
            //don't really care if this gets messed up from the user perspective
            if (User::find($data['user_id'])->role === 3) {
                try {
                    session()->put('submission_id', md5(uniqid('', true)));
                    $data['submission'] = $submission;
                    $dataShop->store('submission', $data, $assignment, $assignment_question);
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);

                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }

        return $response;

    }

    public
    function applyLatePenalyToScore($assignment, $score)
    {
        $late_penalty_percent = $this->latePenaltyPercent($assignment, Carbon::now('UTC'));
        return Round($score * (100 - $late_penalty_percent) / 100, 4);
    }

    private function _computeLatePercent(Assignment $assignment, Carbon $due, Carbon $submitted_at)
    {
        $late_deduction_application_period = $assignment->late_deduction_application_period;
        $late_deduction_percent = 0;
        if ($late_deduction_application_period !== 'once') {
            $late_deduction_percent = $assignment->late_deduction_percent;
            $max_num_iterations = (int)floor(100 / $late_deduction_percent);
            for ($num_late_periods = 0; $num_late_periods < $max_num_iterations; $num_late_periods++) {
                if ($due > $submitted_at) {
                    break;
                }
                $due->add($late_deduction_application_period);
            }
            $late_deduction_percent = $late_deduction_percent * $num_late_periods;
        }
        if ($late_deduction_application_period === 'once' && $submitted_at > $due) {
            $late_deduction_percent = $assignment->late_deduction_percent;
        }
        return $late_deduction_percent;
    }

    /**
     * @param int $user_id
     * @param Assignment $assignment
     * @param Carbon $submitted_at
     * @return float|int
     */
    public function latePenaltyPercentGivenUserId(int $user_id, Assignment $assignment, Carbon $submitted_at)
    {
        //helper function to specifically get the
        $late_deduction_percent = 0;
        if ($assignment->late_policy === 'deduction') {
            $due = Carbon::parse($assignment->assignToTimingDueDateGivenUserId($user_id));
            $late_deduction_percent = $this->_computeLatePercent($assignment, $due, $submitted_at);
        }

        return $late_deduction_percent;
    }

    /**
     * @param Assignment $assignment
     * @param Carbon $now
     * @return float|int
     */
    public
    function latePenaltyPercent(Assignment $assignment, Carbon $now)
    {
        if (session()->get('instructor_user_id')) {
            //logged in as student
            return 0;
        }
        $late_deduction_percent = 0;
        if ($assignment->late_policy === 'deduction') {
            $due = Carbon::parse($assignment->assignToTimingByUser('due'));
            return $this->_computeLatePercent($assignment, $due, $now);
        }

        return $late_deduction_percent;
    }

    /**
     * @param object $submission
     * @param string $technology
     * @param $formatted
     * @return false|mixed|string
     * @throws Exception
     */
    public
    function getStudentResponse(object $submission, string $technology, $formatted = false)
    {

        $submission_object = json_decode($submission->submission);
        $student_response = '';
        switch ($technology) {
            case('h5p'):
                //Log::info(json_encode($submission_object->result));
                $student_response = 'N/A';
                if (isset($submission_object->result->response)) {
                    if (isset($submission_object->object->definition)
                        && isset($submission_object->object->definition->choices)
                        && $submission_object->object->definition->interactionType === 'choice') {
                        $choices = $submission_object->object->definition->choices;
                        foreach ($choices as $choice) {
                            if ((int)$choice->id === (int)$submission_object->result->response) {
                                $student_response = $choice->description->{'en-US'};
                            }
                        }
                    } else {
                        $student_response = $submission_object->result->response;
                    }

                }

                //$correct_response = $submission_object->object->definition->correctResponsesPattern;
                break;
            case('webwork'):
                $student_response = 'N/A';
                $student_response_arr = [];

                if (isset($submission_object->platform) && $submission_object->platform === 'standaloneRenderer') {
                    $answers_arr = json_decode(json_encode($submission_object->score->answers), true);
                    //AnSwEr0003
                    foreach ($answers_arr as $answer_key => $value) {
                        $numeric_key = (int)ltrim(str_replace('AnSwEr', '', $answer_key), 0);
                        $student_response_arr[$numeric_key] = $value['original_student_ans'] ?? '';
                    }

                } else {
                    $JWE = new JWE();
                    $session_JWT = $this->getPayload($submission_object->sessionJWT, $JWE->getSecret('webwork'));
                    //session_JWT will be null for bad submissions
                    if (is_object($session_JWT) && $session_JWT->answersSubmitted) {
                        $answer_template = (array)$session_JWT->answerTemplate;
                        foreach ($answer_template as $key => $value) {
                            if (is_numeric($key) && isset($value['answer']) && isset($value['answer']['original_student_ans'])) {
                                $student_response_arr[$key] = $value['answer']['original_student_ans'];
                            }
                        }
                    }
                }
                if ($student_response_arr) {
                    ksort($student_response_arr);//order by keys
                    $student_response = implode(',', $student_response_arr);
                }
                break;
            case('imathas'):
                $tks = explode('.', $submission_object->state);
                list($headb64, $bodyb64, $cryptob64) = $tks;
                $state = json_decode(base64_decode($bodyb64));
                $student_response = json_decode(json_encode($state->stuanswers), 1);
                if ($student_response) {
                    $student_response = array_values($student_response);
                }
                break;
            case('qti'):
                $submission = json_decode($submission->submission);
                // dd($submission);
                $student_response = $submission->student_response ?: '';
                if ($formatted && $student_response) {
                    $student_response = $this->formattedStudentResponse($submission->question, $student_response);
                }
                break;
        }
        return $student_response;
    }

    /**
     * @param $question
     * @param $student_response
     * @return mixed|string
     */
    public
    function formattedStudentResponse($question, $student_response)
    {
        //Log::info($student_response);
        $student_response = json_decode($student_response);
        if (!$student_response) {
            return $student_response;
        }

        switch ($question->questionType) {
            case('matrix_multiple_response'):
                $student_responses = [];

                $col_headers = [];
                foreach ($question->colHeaders as $key => $col_header) {
                    if ($key !== 0) {
                        $col_headers[] = $col_header;
                    }
                }

                foreach ($question->rows as $row) {
                    foreach ($student_response as $response) {
                        foreach ($row->responses as $row_response_key => $row_response) {
                            if ($response === $row_response->identifier) {
                                $student_responses[$row->header][] = $col_headers[$row_response_key];
                            }
                        }
                    }
                }
                $formatted_student_response = '';
                foreach ($student_responses as $row_header => $responses) {
                    $formatted_student_response .= $row_header . ": " . implode(', ', $responses) . '<hr>';
                }

                break;
            case('select_choice'):
                $student_responses = [];
                foreach ($student_response as $response) {
                    foreach ($question->inline_choice_interactions as $inline_choice_interaction) {
                        foreach ($inline_choice_interaction as $option) {
                            if ($option->value === $response->value) {
                                $student_responses[] = $option->text;
                            }
                        }
                    }
                }
                $formatted_student_response = $student_responses ? implode(', ', $student_responses) : null;
                break;
            case('multiple_answers'):
                $student_responses = [];
                foreach ($student_response as $response) {
                    foreach ($question->simpleChoice as $choice) {
                        if ($choice->identifier === $response) {
                            $student_responses[] = $choice->value;
                        }
                    }
                }
                $formatted_student_response = $student_responses ? implode(', ', $student_responses) : null;
                break;
            case('matching'):
                $possible_matches = json_decode(json_encode($question->possibleMatches), 1);
                $student_responses = [];
                foreach ($student_response as $response) {
                    foreach ($possible_matches as $possible_match) {
                        if ($response->chosenMatchIdentifier === $possible_match['identifier']) {
                            $student_responses[] = str_replace(['<p>', '</p>'], ['', ''], $possible_match['matchingTerm']);
                        }
                    }
                }
                $formatted_student_response = $student_responses ? implode(', ', $student_responses) : null;
                break;
            case('bow_tie'):
                $possible_responses = [];
                foreach (['actionsToTake', 'potentialConditions', 'parametersToMonitor'] as $items) {
                    foreach ($question->{$items} as $item) {
                        $possible_responses[$item->identifier] = $item->value;
                    }
                }

                $formatted_student_response = "Actions to take: ";
                foreach ($student_response->actionsToTake as $action_to_take) {
                    $formatted_student_response .= $possible_responses[$action_to_take] . ', ';
                }
                $formatted_student_response = trim($formatted_student_response, ', ');
                $formatted_student_response .= "  -- Potential conditions: ";
                foreach ($student_response->potentialConditions as $potential_condition) {
                    $formatted_student_response .= $possible_responses[$potential_condition] . ', ';
                }
                $formatted_student_response = trim($formatted_student_response, ', ');
                $formatted_student_response .= "  -- Parameters to monitor: ";
                foreach ($student_response->parametersToMonitor as $parameter_to_monitor) {
                    $formatted_student_response .= $possible_responses[$parameter_to_monitor] . ', ';
                }
                $formatted_student_response = trim($formatted_student_response, ', ');
                break;
            case('matrix_multiple_choice'):
                $formatted_student_response = [];
                $headers = $question->headers;
                foreach ($student_response as $response) {
                    $formatted_student_response[] = $headers[$response + 1]; //first column isn't an actual response
                }
                $formatted_student_response = implode(', ', $formatted_student_response);
                break;
            case('multiple_response_select_all_that_apply'):
                $formatted_student_response = [];
                $responses = [];
                foreach ($question->responses as $item) {
                    $responses[$item->identifier] = $item->value;
                }
                foreach ($student_response as $response) {
                    $formatted_student_response[] = $responses[$response];
                }
                $formatted_student_response = implode(', ', $formatted_student_response);
                break;
            case('highlight_text'):
                $formatted_student_response = [];
                $responses = [];
                foreach ($question->responses as $item) {
                    $responses[$item->identifier] = $item->text;
                }
                foreach ($student_response as $response) {
                    $formatted_student_response[] = $responses[$response];
                }
                $formatted_student_response = implode(', ', $formatted_student_response);
                break;
            case('multiple_response_grouping'):
                $formatted_student_response = [];
                foreach ($question->rows as $row) {
                    $responses = [];
                    $formatted_responses_by_grouping = [];
                    foreach ($row->responses as $item) {
                        $responses[$item->identifier] = $item->value;
                    }
                    foreach ($student_response as $response) {
                        if (isset($responses[$response])) {
                            $formatted_responses_by_grouping[] = $responses[$response];
                        }
                    }
                    $formatted_student_response[] = $row->grouping . ': ' . implode(', ', $formatted_responses_by_grouping);
                }
                $formatted_student_response = implode(' --- ', $formatted_student_response);
                break;
            case('drop_down_table'):
                $formatted_student_response = [];
                foreach ($question->rows as $row) {
                    $responses = [];
                    foreach ($row->responses as $item) {
                        $responses[$item->identifier] = $item->value;
                    }
                    foreach ($student_response as $response) {
                        if (isset($responses[$response])) {
                            $formatted_student_response[] = $row->header . ': ' . $responses[$response];
                        }
                    }
                }
                $formatted_student_response = implode(' --- ', $formatted_student_response);
                break;
            case('highlight_table'):
                $formatted_student_response = [];

                foreach ($question->rows as $row) {
                    $responses = [];
                    foreach ($row->responses as $item) {
                        $responses[$item->identifier] = $item->text;
                    }

                    foreach ($student_response as $response) {
                        if (isset($responses[$response])) {
                            if (!isset($formatted_student_response[$row->header])) {
                                $formatted_student_response[$row->header] = [];
                            }
                            $formatted_student_response[$row->header][] = $responses[$response];
                        }
                    }
                }
                $formatted_student_response_by_row = [];
                foreach ($formatted_student_response as $header => $responses) {
                    $formatted_student_response_by_row[] = $header . ': ' . implode(', ', $responses);
                }

                $formatted_student_response = implode(' --- ', $formatted_student_response_by_row);
                break;
            case('drag_and_drop_cloze'):

                $formatted_student_response = [];
                $responses = [];
                foreach ($question->correctResponses as $response) {
                    $responses[$response->identifier] = $response->value;
                }
                foreach ($question->distractors as $response) {
                    $responses[$response->identifier] = $response->value;
                }
                foreach ($student_response as $response) {
                    $formatted_student_response[] = $responses[$response];
                }
                $formatted_student_response = implode(', ', $formatted_student_response);
                break;
            default:
                $formatted_student_response = $student_response;
        }
        return $formatted_student_response;


    }

    public
    function getSubmissionDatesByAssignmentIdAndUser($assignment_id, User $user)
    {
        $last_submitted_by_user = [];
        $submissions = DB::table('submissions')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $user->id)
            ->select('updated_at', 'question_id')
            ->get();

        foreach ($submissions as $key => $value) {
            $last_submitted_by_user[$value->question_id] = $value->updated_at;
        }

        return $last_submitted_by_user;
    }

    public
    function getSubmissionsCountByAssignmentIdsAndUser(Collection $assignments, Collection $assignment_ids, User $user)
    {

        $assignment_question_submissions = [];
        $assignment_file_submissions = [];
        $assignment_questions = [];
        $results = DB::table('assignment_question')
            ->whereIn('assignment_id', $assignment_ids)
            ->select('assignment_id', 'question_id')
            ->get();
        foreach ($results as $value) {
            $assignment_questions[$value->assignment_id][] = $value->question_id;
        }

        $results = DB::table('randomized_assignment_questions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->select('assignment_id', 'question_id')
            ->get();
        foreach ($results as $value) {
            if (isset($assignment_questions[$value->assignment_id])) {
                unset($assignment_questions[$value->assignment_id]);
            }
        }

        foreach ($results as $value) {
            $assignment_questions[$value->assignment_id][] = $value->question_id;
        }


        $results = DB::table('submissions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->select('question_id', 'assignment_id')
            ->get();
//dd($results);
        foreach ($results as $key => $value) {
            if (!isset($assignment_question_submissions[$value->assignment_id])) {
                $assignment_question_submissions[$value->assignment_id] = [];
            }
            $assignment_question_submissions[$value->assignment_id][] = $value->question_id;

        }

        $results = DB::table('submission_files')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->whereIn('type', ['q', 'text'])
            ->select('question_id', 'assignment_id')
            ->get();

        foreach ($results as $key => $value) {
            $assignment_file_submissions[$value->assignment_id][] = $value->question_id;
        }


        $submissions_count_by_assignment_id = [];

        foreach ($assignments as $assignment) {
            $question_submissions = [];
            $file_submissions = [];
            $total_submissions_for_assignment = 0;
            if (isset($assignment_question_submissions[$assignment->id])) {
                foreach ($assignment_question_submissions[$assignment->id] as $question_id) {
                    $question_submissions[] = $question_id;
                }
            }
            if (isset($assignment_file_submissions[$assignment->id])) {
                foreach ($assignment_file_submissions[$assignment->id] as $question_id) {
                    $file_submissions[] = $question_id;
                }
            }
            if (isset($assignment_questions[$assignment->id])) {

                foreach ($assignment_questions[$assignment->id] as $question_id) {

                    if (in_array($question_id, $question_submissions) || in_array($question_id, $file_submissions)) {

                        $total_submissions_for_assignment++;
                    }
                }
            }

            $submissions_count_by_assignment_id[$assignment->id] = $total_submissions_for_assignment;
        }

        return $submissions_count_by_assignment_id;
    }


    public
    function getNumberOfUserSubmissionsByCourse($course, $user)
    {
        $AssignmentSyncQuestion = new AssignmentSyncQuestion();
        $num_sumbissions_per_assignment = [];
        $assignment_source = [];
        $assignment_ids = collect([]);
        $assignments = $course->assignments;
        if ($assignments->isNotEmpty()) {

            foreach ($course->assignments as $assignment) {
                $assignment_ids[] = $assignment->id;
                $assignment_source[$assignment->id] = $assignment->source;

            }

            $questions_count_by_assignment_id = $AssignmentSyncQuestion->getQuestionCountByAssignmentIds($assignments);

            $submissions_count_by_assignment_id = $this->getSubmissionsCountByAssignmentIdsAndUser($course->assignments, $assignment_ids, $user);

            //set to 0 if there are no questions

            foreach ($assignment_ids as $assignment_id) {
                $num_questions = $questions_count_by_assignment_id[$assignment_id] ?? 0;
                $num_submissions = $submissions_count_by_assignment_id[$assignment_id] ?? 0;
                switch ($assignment_source[$assignment_id]) {
                    case('a'):
                        $num_sumbissions_per_assignment[$assignment_id] = ($num_questions === 0) ? "No questions" : "$num_submissions/$num_questions";
                        break;
                    case('x'):
                        $num_sumbissions_per_assignment[$assignment_id] = 'N/A';
                }
            }

        }
        return $num_sumbissions_per_assignment;


    }

    public
    function computeScoreForCompletion($assignment_question)
    {
        $completion_scoring_factor = 1;
        if (in_array($assignment_question->open_ended_submission_type, ['file', 'audio', 'text'])) {
            if ($assignment_question->completion_scoring_mode === '100% for either') {
                $open_ended_submission_exists = DB::table('submission_files')->where('user_id', Auth::user()->id)
                    ->where('assignment_id', $assignment_question->assignment_id)
                    ->where('question_id', $assignment_question->question_id)
                    ->first();
                if ($open_ended_submission_exists) {
                    $completion_scoring_factor = 0;//don't give more points
                }
            } else {
                $percent = preg_replace('~\D~', '', $assignment_question->completion_scoring_mode);
                $completion_scoring_factor = floatval($percent) / 100;

            }

        }
        return floatval($assignment_question->points) * $completion_scoring_factor;
    }


    public
    function getHintPenalty(int $user_id, Assignment $assignment, int $question_id)
    {
        $viewed_hint = DB::table('shown_hints')
            ->where('user_id', $user_id)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question_id)
            ->first();

        return $viewed_hint && $assignment->hint_penalty ? $assignment->hint_penalty : 0;
    }

    /**
     * @param Assignment $Assignment
     * @param Submission $submission
     * @return bool
     */
    public
    function tooManySubmissions(Assignment $Assignment, Submission $submission): bool
    {
        if (in_array(Request::user()->role, [2, 5])) {
            return false;
        }
        return $Assignment->number_of_allowed_attempts !== 'unlimited'
            && (int)$submission->submission_count === (int)$Assignment->number_of_allowed_attempts;
    }

    /**
     * @param int $user_id
     * @param int $assignment_id
     * @param int $question_id
     * @param int $max_score
     * @return float
     */
    public
    function getH5pVideoInteractionProportionCorrect(int $user_id, int $assignment_id, int $question_id, int $max_score): float
    {
        $h5p_video_interactions = H5pVideoInteraction::where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->get();
        $total_raw = 0;
        foreach ($h5p_video_interactions as $h5p_video_interaction) {
            $partial_submission = json_decode($h5p_video_interaction->submission);
            $total_raw += $partial_submission->result->score->raw;
        }
        return floatval($total_raw / $max_score);


    }

    /**
     * @param object $assignment_question
     * @param object $submission
     * @param StoreSubmission $data
     * @param Assignment $assignment
     * @param Score $score
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param DataShop $dataShop
     * @param string $subContentId
     * @return array
     * @throws Exception
     */
    public
    function processH5PVideoInteraction(
        object                 $assignment_question,
        object                 $submission,
        StoreSubmission        $data,
        Assignment             $assignment,
        Score                  $score,
        AssignmentSyncQuestion $assignmentSyncQuestion,
        DataShop               $dataShop,
        string                 $subContentId): array
    {

        $response['type'] = 'error';
        try {
            $h5pMaxScore = H5pMaxScore::where('question_id', $data['question_id'])->first();
            if (!$h5pMaxScore) {
                $h5pMaxScore = H5pMaxScore::create([
                    'question_id' => $data['question_id'],
                    'max_score' => $data['max_score']
                ]);
            }
            $h5pVideoInteraction = H5pVideoInteraction::where('user_id', $data['user_id'])
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $data['question_id'])
                ->where('sub_content_id', $subContentId)
                ->first();
            DB::beginTransaction();
            if (!$h5pVideoInteraction) {
                $h5pVideoInteraction = H5pVideoInteraction::create(
                    ['user_id' => $data['user_id'],
                        'assignment_id' => $assignment->id,
                        'question_id' => $data['question_id'],
                        'sub_content_id' => $subContentId,
                        'correct' => $this->getH5pVideoInteractionNumCorrect($assignment, json_decode($data['submission'])),
                        'submission' => $data['submission'],
                        'submission_count' => 1
                    ]
                );
            } else {
                if ($h5pVideoInteraction->submission === $data['submission']) {
                    $h5pVideoInteraction->save();
                    $response['type'] = 'info';
                    $response['message'] = 'Partial submission re-saved.';
                    return $response;
                }
                if ($assignment->number_of_allowed_attempts !== 'unlimited'
                    && (int)$h5pVideoInteraction->submission_count === (int)$assignment->number_of_allowed_attempts) {
                    $response['type'] = 'error';
                    $plural = $assignment->number_of_allowed_attempts > 1 ? 's' : '';
                    $response['message'] = "Nothing saved since you are only allowed $assignment->number_of_allowed_attempts attempt$plural.";
                    return $response;
                }
                $h5pVideoInteraction->submission = $data['submission'];
                $h5pVideoInteraction->submission_count = $h5pVideoInteraction->submission_count + 1;
                $h5pVideoInteraction->correct = $this->getH5pVideoInteractionNumCorrect($assignment, json_decode($data['submission']));
                $h5pVideoInteraction->save();
            }

            if ($this->latePenaltyPercent($assignment, Carbon::now('UTC'))) {
                $score_with_late_penalty = $this->applyLatePenalyToScore($assignment, $data['score']);
                if ($score_with_late_penalty < $submission->score) {
                    $response['type'] = 'error';
                    $response['message'] = "With the late deduction, submitting will give you a lower score than you currently have so the submission will not be accepted.";
                    return $response;
                }
            }

            $submission = Submission::where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->first();

            $h5pVideoInteractions = $h5pVideoInteraction->where('user_id', $data['user_id'])
                ->where('assignment_id', $data['assignment_id'])
                ->where('question_id', $data['question_id'])
                ->get();

            $num_correct = 0;
            foreach ($h5pVideoInteractions as $h5pVideoInteraction) {
                $h5p_video_interaction_submission = json_decode($h5pVideoInteraction->submission);
                $num_correct += $this->getH5pVideoInteractionNumCorrect($assignment, $h5p_video_interaction_submission);
            }

            $all_correct = $num_correct === $h5pMaxScore->max_score;
            $data['score'] = ($num_correct / $h5pMaxScore->max_score) * $assignment_question->points;
            $data['score'] = $this->applyLatePenalyToScore($assignment, $data['score']);

            if ($submission) {
                $submission->submission = $data['submission'];
                $submission->answered_correctly_at_least_once = $all_correct || $submission->answered_correctly_at_least_once;
                $submission->score = $data['score'];
                $submission->save();
            } else {
                $submission = Submission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'score' => $data['score'],
                    'answered_correctly_at_least_once' => $all_correct,
                    'submission_count' => 0]);
            }
            $lms_grade_passback = Assignment::find($assignment->id)->lms_grade_passback;
            $score->updateAssignmentScore($data['user_id'], $assignment->id, $lms_grade_passback === 'automatic');
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            try {
                session()->put('submission_id', md5(uniqid('', true)));
                $data['sub_content_id'] = $subContentId;
                $data['submission'] = $submission;
                $dataShop->store('submission', $data, $assignment, $assignment_question);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
            }
            $response['type'] = 'info';
            $response['message'] = 'Partial submission saved.';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving your response.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    function getH5pVideoInteractionNumCorrect(Assignment $assignment, object $submission)
    {
        return $assignment->scoring_type === 'p'
            ? $submission->result->score->raw
            : $submission->result->score->max;
    }

    /**
     * @param $response
     * @param $student_responses
     * @return int
     * @throws Exception
     */
    public
    function computeScoreFromPlusMinusScoring($response, $student_responses): int
    {
        if ($response->correctResponse && in_array($response->identifier, $student_responses)) {
            return 1;
        }
        if ($response->correctResponse && !in_array($response->identifier, $student_responses)) {
            return -1;
        }
        if (!$response->correctResponse && in_array($response->identifier, $student_responses)) {
            return -1;
        }
        if (!$response->correctResponse && !in_array($response->identifier, $student_responses)) {
            return 0;
        }
        throw new Exception ('Error in plus/minus scoring logic.');
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param $submission
     * @param bool $is_learning_tree_node
     * @return array
     * @throws Exception
     */
    public
    function getSubmissionArray(Assignment $assignment, Question $question, $submission, bool $is_learning_tree_node = false): array
    {
        $submission_array = [];

        if ($submission &&
            in_array($question->technology, ['webwork', 'imathas'])
            && (in_array(request()->user()->role, [2, 5]) || (in_array($assignment->assessment_type, ['learning tree', 'real time']) || ($assignment->assessment_type === 'delayed' && $assignment->solutions_released)))) {
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $submission_info = json_decode($submission->submission, 1);
            switch ($question->technology) {
                case('webwork'):
                    if ($submission_info && isset($submission_info['score']) && isset($submission_info['score']['answers'])) {
                        foreach ($submission_info['score']['answers'] as $identifier => $value) {
                            if (isset($value['preview_latex_string'])) {
                                $formatted_submission = '\(' . $value['preview_latex_string'] . '\)';
                            } else {
                                $formatted_submission = $value['original_student_ans'] ?? 'Nothing submitted.';
                            }
                            $value['score'] = $value['score'] ?? 0;
                            $is_correct = $value['score'] === 1;
                            $weight = isset($value['weight']) ? $value['weight'] / 100 : (1 / count($submission_info['score']['answers']));
                            $points = !$is_learning_tree_node && count($submission_info['score']['answers'])
                                ? Helper::removeZerosAfterDecimal(round($assignment_question->points * (+$value['score'] * $weight), 4))
                                : 0;
                            $percent = !$is_learning_tree_node && $assignment_question->points > 0 ? Helper::removeZerosAfterDecimal(round(100 * $points / $assignment_question->points, 2)) : 0;

                            $submission_array_value = ['submission' => $formatted_submission,
                                'identifier' => $identifier,
                                'correct' => $is_correct,
                                'partial_credit' => !$is_correct && $value['score'] > 0,
                                'points' => $points,
                                'percent' => $percent];
                            if (request()->user()->role === 2) {
                                $correct_ans = $value['correct_ans_latex_string'] ?? $value['correct_ans'] ?? "This WeBWorK question is missing the 'correct_ans' key.  Please fix the weBWork code.";
                                $submission_array_value['correct_ans'] = '\(' . $correct_ans . '\)';
                            }
                            $submission_array[] = $submission_array_value;
                        }
                    }
                    break;
                case('imathas'):
                    if ($submission_info) {
                        $tks = explode('.', $submission_info['state']);
                        list($headb64, $bodyb64, $cryptob64) = $tks;
                        $state = json_decode(base64_decode($bodyb64), 1);
                        $raw_scores = array_values($state['rawscores']);
                        //Log::info(print_r($raw_scores, 1));
                        if (isset($state['stuanswers']) && $state['stuanswers']) {
                            foreach ($state['stuanswers'] as $key => $submission) {
                                if (is_array($submission)) {
                                    foreach ($submission as $part_key => $part) {
                                        $raw = $submission_info['raw'][$part_key];
                                        $points = !$is_learning_tree_node ? $this->getPoints($assignment_question, $raw, $submission) : 0;
                                        $percent = !$is_learning_tree_node ? $this->getPercent($assignment_question, $points) : 0;

                                        $submission_array_value = [
                                            'submission' => $part !== '' ? '\(' . $part . '\)' : 'Nothing submitted.',
                                            'correct' => $raw === 1,
                                            'points' => $points,
                                            'percent' => $percent];
                                        $submission_array[] = $submission_array_value;
                                    }
                                } else {
                                    $points = !$is_learning_tree_node ? $this->getPoints($assignment_question, $submission_info['raw'][0], [$submission]) : 0;
                                    $percent = !$is_learning_tree_node ? $this->getPercent($assignment_question, $points) : 0;
                                    $submission_array[] = ['submission' => $submission !== '' ? '\(' . $submission . '\)' : 'Nothing submitted.',
                                        'points' => $points,
                                        'percent' => $percent,
                                        'correct' => $submission_info['raw'][0] === 1];

                                }
                            }
                        }
                    }
                    break;
                default:
                    throw new Exception ("$question->technology is not set up for a submission array.");

            }
        }
        return $submission_array;
    }

    /**
     * @param $assignment_question
     * @param $score
     * @param $answers
     * @return int|mixed|string
     */
    public
    function getPoints($assignment_question, $score, $answers)
    {
        return count($answers)
            ? Helper::removeZerosAfterDecimal(round($assignment_question->points * (+$score / count($answers)), 4))
            : 0;
    }

    /**
     * @param $assignment_question
     * @param $points
     * @return int|mixed|string
     */
    public
    function getPercent($assignment_question, $points)
    {
        return $assignment_question->points > 0 ? Helper::removeZerosAfterDecimal(round(100 * $points / $assignment_question->points, 2)) : 0;
    }

    private
    function _returnJsonAndExit($result)
    {
        header('appversion: ignore');
        echo json_encode($result);
        exit;
    }
}


