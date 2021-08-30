<?php

namespace App;

use App\Exceptions\Handler;
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

class Submission extends Model
{

    use DateFormatter;
    use JWT;

    protected $guarded = [];

    /**
     * @throws Exception
     */
    public function getAutoGradedSubmissionsByUser($enrolled_users, $assignment, $question)
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
                    'score' => rtrim(rtrim($submission->score, "0"), ".")
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
     * @param StoreSubmission $request
     * @param Submission $submission
     * @param Assignment $Assignment
     * @param Score $score
     * @param LtiLaunch $ltiLaunch
     * @param LtiGradePassback $ltiGradePassback
     * @param DataShop $dataShop
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function store(StoreSubmission $request,
                          Submission $submission,
                          Assignment $Assignment,
                          Score $score,
                          LtiLaunch $ltiLaunch,
                          LtiGradePassback $ltiGradePassback,
                          DataShop $dataShop,
                          AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';//using an alert instead of a noty because it wasn't working with post message

        // $data = $request->validated();//TODO: validate here!!!!!
        // $data = $request->all(); ///maybe request->all() flag in the model or let it equal request???
        // Log::info(print_r($request->all(), true));
        $data = $request;

        $data['user_id'] = Auth::user()->id;
        $assignment = $Assignment->find($data['assignment_id']);

        $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)
            ->where('question_id', $data['question_id'])
            ->select('points', 'open_ended_submission_type')
            ->first();

        if (!$assignment_question) {
            $response['message'] = 'That question is not part of the assignment.';
            return $response;
        }


        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $data['question_id']]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        switch ($data['technology']) {
            case('h5p'):
                $submission = json_decode($data['submission']);
                $proportion_correct = (floatval($submission->result->score->raw) / floatval($submission->result->score->max));
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                break;
            case('imathas'):
                $submission = $data['submission'];
                $proportion_correct = floatval($submission->score);
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


                $submission_score = (object)$submission->score; //
                //Log::info( $submission_score->result);
                if (!isset($submission_score->result)) {
                    throw new Exception ('Please refresh the page and resubmit to use the upgraded Webwork renderer.');
                }
                $proportion_correct = floatval($submission_score->result);
                $data['score'] = $assignment->scoring_type === 'p'
                    ? floatval($assignment_question->points) * $proportion_correct
                    : $this->computeScoreForCompletion($assignment_question);
                $data['submission'] = json_encode($data['submission']);
                break;
            default:
                $response['message'] = 'That is not a valid technology.';
                return $response;
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
            $explored_learning_tree = 0;
            $message = 'Auto-graded submission saved.';
            if ($submission) {
                if ($assignment->assessment_type === 'real time') {
                    $response['message'] = 'You can only submit once since you are provided with real-time feedback.';
                    return $response;
                }

                if (($assignment->assessment_type === 'learning tree')) {
                    $explored_learning_tree = $submission->explored_learning_tree;
                    if (!$explored_learning_tree && (int)$submission->submission_count >= 1) {
                        $response['type'] = 'info';
                        $response['learning_tree_message'] = true;
                        $response['message'] = 'You can resubmit after spending time exploring the Learning Tree.';
                        return $response;
                    }

                    if ($submission->answered_correctly_at_least_once) {
                        $data['score'] = $submission->submission_score;
                        $response['type'] = 'info';
                        $response['not_updated_message'] = true;
                        $response['message'] = "Your score was not updated since you already answered this question correctly.";
                        return $response;
                    }


                    //1 submission, get 100%
                    //submission penalty is 10%
                    //2 submissions, get 1-(1*10/100) = 90%


                    $proportion_of_score_received = max(1 - (($submission->submission_count - 1) * $assignment->submission_count_percent_decrease / 100), 0);
                    $learning_tree_percent_penalty = 100 * (1 - $proportion_of_score_received);
                    if ($explored_learning_tree) {
                        $learning_tree_points = (floatval($assignment->percent_earned_for_exploring_learning_tree) / 100) * floatval($assignment_question->points);
                        if ($data['all_correct']) {
                            $submission->answered_correctly_at_least_once = 1;//first time getting it right!

                            $data['score'] = max(floatval($assignment_question->points) * $proportion_of_score_received, $learning_tree_points);
                            $message = "Your total score was updated with a penalty of $learning_tree_percent_penalty% applied.";
                        } else {
                            $data['score'] = $learning_tree_points;
                            $s = $learning_tree_points !== 1 ? 's' : '';
                            $message = "Incorrect! But you're still receiving $learning_tree_points point$s for exploring the Learning Tree.";
                        }
                    } else {
                        if (!$data['all_correct']) {
                            $message = "Your submission was not correct so your score was not updated.";
                        }
                    }
                }
                DB::beginTransaction();
                $submission->submission = $data['submission'];
                $submission->score = $this->applyLatePenalyToScore($assignment, $data['score']);
                $submission->submission_count = $submission->submission_count + 1;
                $submission->save();

            } else {
                if (($assignment->assessment_type === 'learning tree')) {
                    if (!$data['all_correct']) {
                        $data['score'] = 0;
                        $message = "Unfortunately, you did not answer this question correctly.  Explore the Learning Tree, and then you can try again!";
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

            $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->assessment_type, $ltiLaunch, $ltiGradePassback);


            $score_not_updated = ($learning_tree->isNotEmpty() && !$data['all_correct']);
            if (\App::runningUnitTests()) {
                $response['submission_id'] = $submission->id;
            }
            $response['type'] = $score_not_updated ? 'info' : 'success';
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            $response['message'] = $message;
            $response['learning_tree'] = ($learning_tree->isNotEmpty() && !$data['all_correct']) ? json_decode($learning_tree[0]->learning_tree)->blocks : '';
            $response['learning_tree_percent_penalty'] = "$learning_tree_percent_penalty%";
            $response['explored_learning_tree'] = $explored_learning_tree;
            $response['learning_tree_message'] = !$explored_learning_tree;

            //don't really care if this gets messed up from the user perspective
            try {
                session()->put('submission_id', md5(uniqid('', true)));
                $dataShop->store($submission, $data);
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

    public function applyLatePenalyToScore($assignment, $score)
    {
        $late_penalty_percent = $this->latePenaltyPercent($assignment, Carbon::now('UTC'));
        return Round($score * (100 - $late_penalty_percent) / 100, 2);
    }

    public function latePenaltyPercent(Assignment $assignment, Carbon $now)
    {
        if (session()->get('instructor_user_id')) {
            //logged in as student
            return 0;
        }
        $late_deduction_percent = 0;
        if ($assignment->late_policy === 'deduction') {
            $late_deduction_percent = $assignment->late_deduction_percent;
            $late_deduction_application_period = $assignment->late_deduction_application_period;
            if ($assignment->late_deduction_application_period !== 'once') {
                $max_num_iterations = (int)floor(100 / $late_deduction_percent);
                //Ex 100/52 = 1.92....use 1.  Makes sense since you won't deduct more than 100%
                //Ex 100/50 = 2.
                $due = Carbon::parse($assignment->assignToTimingByUser('due'));
                for ($num_late_periods = 0; $num_late_periods < $max_num_iterations; $num_late_periods++) {
                    if ($due > $now) {
                        break;
                    }
                    $due->add($late_deduction_application_period);
                }
                $late_deduction_percent = $late_deduction_percent * $num_late_periods;
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
    public function getStudentResponse(object $submission, string $technology)
    {

        $submission_object = json_decode($submission->submission);
        $student_response = '';
        switch ($technology) {
            case('h5p'):
                //Log::info(json_encode($submission_object->result));
                $student_response = 'N/A';
                if (isset($submission_object->result->response)) {
                    if (isset($submission_object->object->definition) && $submission_object->object->definition->interactionType === 'choice') {
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

        }
        return $student_response;
    }

    public function getSubmissionDatesByAssignmentIdAndUser($assignment_id, User $user)
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

    public function getSubmissionsCountByAssignmentIdsAndUser(Collection $assignments, Collection $assignment_ids, User $user)
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


    public function getNumberOfUserSubmissionsByCourse($course, $user)
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

    public function computeScoreForCompletion($assignment_question)
    {
        $open_ended_submission_type_factor = in_array($assignment_question->open_ended_submission_type, ['file', 'audio', 'text']) ? .5 : 1;
        return floatval($assignment_question->points) * $open_ended_submission_type_factor;
    }
}
