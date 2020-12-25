<?php

namespace App;

use App\Exceptions\Handler;
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

    protected $guarded = [];


    public function store(StoreSubmission $request, Submission $submission, Assignment $Assignment, Score $score)
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
            ->select('points')
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
                $data['score'] = floatval($assignment_question->points) * $proportion_correct;
                break;
            case('imathas'):
                $submission = $data['submission'];
                $proportion_correct = floatval($submission->score);
                $data['score'] = floatval($assignment_question->points) * $proportion_correct;
                $data['submission'] = json_encode($data['submission'], JSON_UNESCAPED_SLASHES);
                break;
            case('webwork'):
                // Log::info('case webwork');
                $submission = $data['submission'];
                $proportion_correct = floatval($submission->score->score);
                $data['score'] = floatval($assignment_question->points) * $proportion_correct;
                Log::info('Score: ' . $data['score']);
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
            $message = 'Question submission saved. Your scored was updated.';

            if ($submission) {
                if ($assignment->assessment_type === 'real time') {
                    $response['message'] = 'You can only submit once since you are provided with real-time feedback.';
                    return $response;
                }

                if (($assignment->assessment_type === 'learning tree')) {
                    $explored_learning_tree = $submission->explored_learning_tree;
                    if (!$explored_learning_tree && (int)$submission->submission_count >= 1) {
                        $response['type'] = 'info';
                        $response['message'] = 'You can resubmit after spending time exploring the Learning Tree.';
                        return $response;
                    }

                    if ($submission->answered_correctly_at_least_once) {
                        $data['score'] = $submission->submission_score;
                        $response['type'] = 'info';
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
                            $message = "You submission was not correct but you're still receiving $learning_tree_points points for exploring the Learning Tree.";
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
                        $message = "Unfortunately, you didn't answer this question correctly.  Explore the Learning Tree, and then you can try again!";
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
            switch ($assignment->scoring_type) {
                case 'c':
                    $num_submissions_by_assignment = DB::table('submissions')
                        ->where('user_id', $data['user_id'])
                        ->where('assignment_id', $assignment->id)
                        ->count();
                    if ((int)$num_submissions_by_assignment === count($assignment->questions)) {
                        Score::updateOrCreate(['user_id' => $data['user_id'],
                            'assignment_id' => $assignment->id],
                            ['score' => 'c']);
                    }
                    break;
                case 'p':
                    $score->updateAssignmentScore($data['user_id'], $assignment->id, $assignment->submission_files);
                    break;
            }
            $score_not_updated = ($learning_tree->isNotEmpty() && !$data['all_correct']);
            if (env('DB_DATABASE') === "test_libretext") {
                $response['submission_id'] = $submission->id;
            }
            $response['type'] = $score_not_updated ? 'info' : 'success';
            $response['message'] = $message;
            $response['learning_tree'] = ($learning_tree->isNotEmpty() && !$data['all_correct']) ? json_decode($learning_tree[0]->learning_tree)->blocks : '';
            $response['learning_tree_percent_penalty'] = "$learning_tree_percent_penalty%";
            $response['explored_learning_tree'] = $explored_learning_tree;
            $log = new \App\Log();
            $request->action = 'submit-question-response';
            $request->data = ['assignment_id' => $data['assignment_id'],
                'question_id' => $data['question_id']];
            $log->store($request);
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
        $late_deduction_percent = 0;
        if ($assignment->late_policy === 'deduction') {
            $late_deduction_percent = $assignment->late_deduction_percent;
            $late_deduction_application_period = $assignment->late_deduction_application_period;
            if ($assignment->late_deduction_application_period !== 'once') {
                $max_num_iterations = (int)floor(100 / $late_deduction_percent);
                //Ex 100/52 = 1.92....use 1.  Makes sense since you won't deduct more than 100%
                //Ex 100/50 = 2.
                $due = Carbon::parse($assignment->due);
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
            ->where('type', 'q')
            ->select('question_id', 'assignment_id')
            ->get();
        foreach ($results as $key => $value) {
            $assignment_file_submissions[$value->assignment_id][] = $value->question_id;
        }


        $submissions_count_by_assignment_id = [];
        foreach ($assignments as $assignment) {
            $question_submissions = [];
            $file_submissions = [];
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
            $total_submissions_for_assignment = 0;
            foreach ($assignment->questions as $question) {
                if (in_array($question->id, $question_submissions) || in_array($question->id, $file_submissions)) {
                    $total_submissions_for_assignment++;
                }

                $submissions_count_by_assignment_id[$assignment->id] = $total_submissions_for_assignment;
            }

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

            $questions_count_by_assignment_id = $AssignmentSyncQuestion->getQuestionCountByAssignmentIds($assignment_ids);

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
}
