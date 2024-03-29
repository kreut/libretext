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
                $proportion_correct = floatval($score->result);
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
                            echo json_encode($response);
                            exit;
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
                            echo json_encode($response);
                            exit;
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
                                echo json_encode($response);
                                exit;
                            }
                            $penalty = -1;
                        }
                        if ($question_type === 'multiple_response_select_n') {
                            $number_to_select = +$submission->question->numberToSelect;
                            if (count($student_responses) !== $number_to_select) {
                                $response['message'] = "Please check $number_to_select boxes before submitting.";
                                $response['type'] = 'error';
                                echo json_encode($response);
                                exit;
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
                        $student_response = json_decode($submission->student_response);
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
                                echo json_encode($response);
                                exit;
                            }
                        }

                        foreach ($student_response as $value) {
                            if (in_array($value->chosenMatchIdentifier, $chosen_match_identifiers)) {
                                $response['message'] = "Each matching term should be chosen only once.";
                                if (app()->environment('testing')) {
                                    throw new Exception ($response['message']);
                                }
                                echo json_encode($response);
                                exit;
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
                            echo json_encode($response);
                            exit;
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
                            echo json_encode($response);
                            exit;
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
                            echo json_encode($response);
                            exit;
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
                           if (app()->environment() === 'testing') {
                               throw new Exception($result['message']);
                           }
                           echo json_encode($result);
                           exit;
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
            $learning_tree_percent_penalty = 0;
            $learning_tree_success_criteria_satisfied = 0;
            $message = 'Auto-graded submission saved.';

            $hint_penalty = in_array($assignment->assessment_type, ['real time', 'learning tree'])
                ? $this->getHintPenalty($data['user_id'], $assignment, $data['question_id'])
                : 0;

            if ($submission) {
                if (in_array($assignment->assessment_type, ['real time', 'learning tree'])) {
                    $too_many_submissions = $this->tooManySubmissions($assignment, $submission);
                    if ($too_many_submissions) {
                        $plural = $assignment->number_of_allowed_attempts === '1' ? '' : 's';
                        $message = "You are only allowed $assignment->number_of_allowed_attempts attempt$plural.  ";
                        $response['message'] = $message;
                        if ($assignment->assessment_type === 'learning tree') {
                            $too_many_resets = $this->tooManyResets($assignment, $assignment_question->id, $data['learning_tree_id'], $data['user_id'], $submission->reset_count, 'greater than or equal to');
                            $response['learning_tree_message'] = true;
                            $response['message'] = $message;
                            $response['message'] .= $too_many_resets
                                ? "<br/></br>And, unfortunately, you have no more resets available."
                                : "<br/></br>However, by successfully exploring the Learning Tree, you can still receive a reset to get $assignment->number_of_allowed_attempts more attempt$plural.";
                            $response['traffic_light_color'] = $too_many_resets
                                ? 'red'
                                : 'yellow';
                            $response['type'] = 'info';
                        }
                        return $response;
                    }
                    if ($assignment->assessment_type === 'learning tree') {
                        $learning_tree_success_criteria_satisfied = $submission->learning_tree_success_criteria_satisfied;
                        $too_many_resets = $this->tooManyResets($assignment, $assignment_question->id, $data['learning_tree_id'], $data['user_id'], $submission->reset_count, 'greater than or equal to');
                        if ($too_many_resets) {
                            $attempts_left = $assignment->number_of_allowed_attempts - ($submission->submission_count + 1);
                            $message = "Unfortunately this was not correct and you have no remaining resets.";
                            $brs = "<br><br>";
                            if ($assignment->number_of_allowed_attempts === 'unlimited') {
                                $response['traffic_light_color'] = 'yellow';
                                $message .= $brs;
                                $message .= $assignment->number_of_allowed_attempts_penalty
                                    ? "You can re-try the Root Assessment with a penalty of $assignment->number_of_allowed_attempts_penalty%  per attempt."
                                    : "You can re-try the Root Assessment an unlimited number of times without penalty.";
                            } else {
                                if ($attempts_left) {
                                    $response['traffic_light_color'] = 'yellow';
                                    $message .= $brs;
                                    $plural = $attempts_left > 1 ? 's' : '';
                                    $message .= $assignment->number_of_allowed_attempts_penalty
                                        ? "You can re-try the Root Assessment $attempts_left more time$plural with a penalty of $assignment->number_of_allowed_attempts_penalty% per attempt."
                                        : "You can re-try the Root Assessment $attempts_left more time$plural.";
                                } else {
                                    $response['traffic_light_color'] = 'red';
                                }
                            }
                        } else {
                            //dd($learning_tree_success_criteria_satisfied);
                            if (!$learning_tree_success_criteria_satisfied) {
                                //have a submission and are trying it again in a state where the success criteria isn't yet satisfied
                                $message = "Correct re-do and you're done!";
                                $proportion_of_score_received = 1 - ($hint_penalty / 100);
                                $data['score'] = $data['score'] * $proportion_of_score_received;
                                if (!$data['all_correct']) {
                                    $assignment_question_id = DB::table('assignment_question')
                                        ->where('assignment_id', $data['assignment_id'])
                                        ->where('question_id', $data['question_id'])
                                        ->select('id')
                                        ->first()
                                        ->id;
                                    $message = "Unfortunately, you still did not answer this question correctly.<br><br>{$this->getLearningTreeMessage( $assignment_question_id)}";
                                    if (!$submission->reset_count) {
                                        $message .= "<br><br>To navigate through the tree, you can use the left and right arrows, located above the Root Assessment.";
                                    }
                                    $response['traffic_light_color'] = 'yellow';
                                }
                            }

                        }
                    }
                    $num_deductions_to_apply = $submission->submission_count;
                    if ($assignment->assessment_type === 'learning tree') {
                        $assignmentQuestionLearningTree = new AssignmentQuestionLearningTree();
                        $assignment_question_learning_tree = $assignmentQuestionLearningTree->getAssignmentQuestionLearningTreeByRootNodeQuestionId($assignment->id, $data['question_id']);
                        if ($assignment_question_learning_tree->free_pass_for_satisfying_learning_tree_criteria) {
                            $num_deductions_to_apply--;
                        }
                    }
                    $proportion_of_score_received = 1 - (($num_deductions_to_apply * $assignment->number_of_allowed_attempts_penalty + $hint_penalty) / 100);
                    // Log::info($submission->score . ' ' . $data['score'] . ' ' . $num_deductions_to_apply . ' ' . $assignment->number_of_allowed_attempts_penalty . ' ' . $hint_penalty . ' ' . $proportion_of_score_received);
                    $data['score'] = max($data['score'] * $proportion_of_score_received, 0);
                    if ($proportion_of_score_received < 1 && $data['score'] < $submission->score) {
                        $response['type'] = 'error';
                        $response['message'] = "With the number of attempts and hint penalty applied, submitting will give you a lower score than you currently have, so the submission will not be accepted.";
                        return $response;
                    }
                }

                if ($this->latePenaltyPercent($assignment, Carbon::now('UTC'))) {
                    $score_with_late_penalty = $this->applyLatePenalyToScore($assignment, $data['score']);
                    if ($score_with_late_penalty < $submission->score) {
                        $response['type'] = 'error';
                        $response['message'] = "With the late deduction, submitting will give you a lower score than you currently have so the submission will not be accepted.";
                        return $response;
                    }
                }
                DB::beginTransaction();
                $submission->submission = $data['submission'];
                $submission->answered_correctly_at_least_once = $data['all_correct'];
                $submission->score = $this->applyLatePenalyToScore($assignment, $data['score']);
                $submission->submission_count = $submission->submission_count + 1;
                $submission->save();

            } else {
                $proportion_of_score_received = 1 - ($hint_penalty / 100);
                $data['score'] = $data['score'] * $proportion_of_score_received;
                if (($assignment->assessment_type === 'learning tree')) {
                    if (!$data['all_correct']) {
                        $assignment_question_id = DB::table('assignment_question')
                            ->where('assignment_id', $data['assignment_id'])
                            ->where('question_id', $data['question_id'])
                            ->select('id')
                            ->first()
                            ->id;
                        $message = "Unfortunately, you did not answer this question correctly.  {$this->getLearningTreeMessage($assignment_question_id)}";
                        $message .= "<br><br>To navigate through the tree, you can use the left and right arrows, located above the Root Assessment.";
                        $response['traffic_light_color'] = 'yellow';
                    }
                }
                DB::beginTransaction();

                $submission = Submission::create(['user_id' => $data['user_id'],
                    'assignment_id' => $data['assignment_id'],
                    'question_id' => $data['question_id'],
                    'submission' => $data['submission'],
                    'score' => $this->applyLatePenalyToScore($assignment, $data['score']),
                    'answered_correctly_at_least_once' => $data['all_correct'],
                    'reset_count' => $assignment->assessment_type === 'learning tree' ? 0 : null,
                    'submission_count' => 1]);
            }
            //update the score if it's supposed to be updated

            $score->updateAssignmentScore($data['user_id'], $assignment->id);
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);

            $score_not_updated = ($learning_tree->isNotEmpty() && !$data['all_correct']);
            if (\App::runningUnitTests()) {
                $response['submission_id'] = $submission->id;
            }
            $response['type'] = $score_not_updated ? 'info' : 'success';
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            $response['message'] = $message;
            $response['learning_tree'] = ($learning_tree->isNotEmpty() && !$data['all_correct']) ? json_decode($learning_tree[0]->learning_tree)->blocks : '';
            $response['learning_tree_percent_penalty'] = "$learning_tree_percent_penalty%";
            $response['learning_tree_success_criteria_satisfied'] = $learning_tree_success_criteria_satisfied;
            $response['learning_tree_message'] = !$learning_tree_success_criteria_satisfied && !$data['all_correct'];

            //don't really care if this gets messed up from the user perspective
            try {
                session()->put('submission_id', md5(uniqid('', true)));
                $data['submission'] = $submission;
                $dataShop->store('submission', $data, $assignment, $assignment_question);
            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);

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

    public
    function latePenaltyPercent(Assignment $assignment, Carbon $now)
    {
        if (session()->get('instructor_user_id')) {
            //logged in as student
            return 0;
        }
        $late_deduction_percent = 0;

        if ($assignment->late_policy === 'deduction') {
            $late_deduction_application_period = $assignment->late_deduction_application_period;
            $due = Carbon::parse($assignment->assignToTimingByUser('due'));
            if ($late_deduction_application_period !== 'once') {
                $late_deduction_percent = $assignment->late_deduction_percent;
                $max_num_iterations = (int)floor(100 / $late_deduction_percent);
                //Ex 100/52 = 1.92....use 1.  Makes sense since you won't deduct more than 100%
                //Ex 100/50 = 2.
                for ($num_late_periods = 0; $num_late_periods < $max_num_iterations; $num_late_periods++) {
                    if ($due > $now) {
                        break;
                    }
                    $due->add($late_deduction_application_period);
                }
                $late_deduction_percent = $late_deduction_percent * $num_late_periods;
            }
            if ($late_deduction_application_period === 'once' && $now > $due) {
                $late_deduction_percent = $assignment->late_deduction_percent;
            }
            return $late_deduction_percent;
        }

        return $late_deduction_percent;
    }

    /**
     * @param object $submission
     * @param string $technology
     * @return false|string
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

                $student_response = json_encode($state->stuanswers);
                //$correct_response = 'N/A';
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
    public function formattedStudentResponse($question, $student_response)
    {
        $student_response = json_decode($student_response);
        if (!$student_response) {
            return $student_response;
        }
        switch ($question->questionType) {
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

        foreach ($results as $key => $value) {
            $assignment_question_submissions[$value->assignment_id][] = $value->question_id;
        }
        $results = DB::table('submissions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $user->id)
            ->select('question_id', 'assignment_id')
            ->get();
        foreach ($results as $key => $value) {
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

    /**
     * @param $assignment
     * @param $assignment_question_id
     * @param $learning_tree_id
     * @param $user_id
     * @param $reset_count
     * @param $inequality
     * @return bool
     * @throws Exception
     */
    public
    function tooManyResets($assignment, $assignment_question_id, $learning_tree_id, $user_id, $reset_count, $inequality): bool
    {
        $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
            ->where('assignment_question_id', $assignment_question_id)
            ->first();

        switch ($assignment_question_learning_tree->learning_tree_success_level) {
            case('branch'):
                $applied_to_reset_branches = DB::table('learning_tree_successful_branches')
                    ->where('assignment_id', $assignment->id)
                    ->where('learning_tree_id', $learning_tree_id)
                    ->where('user_id', $user_id)
                    ->where('applied_to_reset', 1)
                    ->count();
                switch ($inequality) {
                    case('greater than'):
                        $too_many_resets = $reset_count && ($applied_to_reset_branches > $assignment_question_learning_tree->number_of_resets * $assignment_question_learning_tree->number_of_successful_branches_for_a_reset);
                        break;
                    case('greater than or equal to'):
                        $too_many_resets = $reset_count && ($applied_to_reset_branches >= $assignment_question_learning_tree->number_of_resets * $assignment_question_learning_tree->number_of_successful_branches_for_a_reset);
                        break;
                    default:
                        throw new Exception("Not a valid inequality");
                }
                break;
            case('tree'):
                $too_many_resets = $reset_count >= 1;
                break;
            default:
                throw new Exception("Not a valid inequality");

        }

        return $too_many_resets;
    }


    /**
     * @param $assignment_question_id
     * @return string
     */
    public
    function getLearningTreeMessage($assignment_question_id): string
    {


        $assignment_question_learning_tree = DB::table('assignment_question_learning_tree')
            ->where('assignment_question_id', $assignment_question_id)
            ->first();

        $plural_min_number = $assignment_question_learning_tree->min_number_of_successful_assessments > 1 ? "s" : "";
        $plural_min_time = $assignment_question_learning_tree->min_time > 1 ? "s" : "";
        $message = "You will receive a reset for the Root Assessment after you have ";
        if ($assignment_question_learning_tree->learning_tree_success_criteria === 'assessment based') {
            $message .= "successfully completed at least $assignment_question_learning_tree->min_number_of_successful_assessments assessment$plural_min_number ";
        }
        if ($assignment_question_learning_tree->learning_tree_success_criteria === 'time based') {
            $message .= "spent at least $assignment_question_learning_tree->min_time minute$plural_min_time ";
        }
        if ($assignment_question_learning_tree->learning_tree_success_level === 'branch') {
            $message .= "on $assignment_question_learning_tree->number_of_successful_branches_for_a_reset of the uncompleted branches.";
        }
        if ($assignment_question_learning_tree->learning_tree_success_level === 'tree') {
            $message .= "in the Learning Tree.";
        }

        return $message;
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
                    'reset_count' => null,
                    'submission_count' => 0]);
            }
            $score->updateAssignmentScore($data['user_id'], $assignment->id);
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
}


