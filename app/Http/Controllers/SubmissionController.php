<?php

namespace App\Http\Controllers;

use App\AssignmentSyncQuestion;
use App\Exceptions\Handler;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use \Exception;
use App\Submission;
use App\Score;
use App\Assignment;
use App\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Db;

use App\Http\Requests\StoreSubmission;


class SubmissionController extends Controller
{

    public function store(StoreSubmission $request, Assignment $Assignment, Score $score)
    {
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), $Assignment, $score);

    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @return array
     * @throws Exception
     */
    public function submissionPieChartData(Assignment $assignment, Question $question, Submission $submission, AssignmentSyncQuestion $assignmentSyncQuestion)
    {
        $response['type'] = 'error';
        $response['redirect_question'] = false;
        $response['pie_chart_data'] = [];
        $authorized = Gate::inspect('submissionPieChartData', [$submission, $assignment, $question]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $question_info = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $past_due = time() > strtotime($assignment->due);
            if (Auth::user()->role === 3 && !$question_info->clicker_start && !$past_due){
                $clicker_question = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->whereNotNull('clicker_start')
                    ->select('question_id')
                    ->first();
                $response['type'] = 'success';
                $response['redirect_question'] = $clicker_question->question_id;
                return $response;
            }
            $response['clicker_status'] = $assignmentSyncQuestion->getFormattedClickerStatus($question_info);

            if (Auth::user()->role === 3) {
                $due= DB::table('assignments')
                    ->where('id', $assignment->id)
                    ->select('due')
                    ->first()
                    ->due;
                if (time() <= strtotime($due)) {
                    $response['type'] = 'success';
                    return $response;
                }

            }
            $number_enrolled = count($assignment->course->enrollments)-1;//don't include Fake Student

            $fake_student_user_id= DB::table('enrollments')->where('course_id', $assignment->course->id)
                                                        ->orderBy('user_id')
                                                        ->first()
                                                        ->user_id;
            $submission_results = DB::table('submissions')
                ->join('questions', 'submissions.question_id', '=', 'questions.id')
                ->where('submissions.assignment_id', $assignment->id)
                ->where('submissions.question_id', $question->id)
                ->where('submissions.user_id','<>', $fake_student_user_id)
                ->select('submission', 'technology')
                ->get();

            $choices = [];
            $counts = [];
            foreach ($submission_results as $key => $value) {
                $submission = json_decode($value->submission, true);
                //Log::info(print_r($submission, true));

                $technology = $value->technology;
                switch ($technology) {
                    case('h5p'):
                        $object = $submission['object'];
                        //Log::info(print_r($submission, true));
                        // Log::info($object['definition']['interactionType']);
                        switch ($object['definition']['interactionType']) {
                            case('choice'):
                                if (!$choices) {
                                    $choices = $this->getChoices($technology, $object['definition']);
                                    foreach ($choices as $choice) {
                                        $counts[] = 0;
                                    }
                                    $response['choices'] = $choices;
                                    $correct_answer_index = $object['definition']['correctResponsesPattern'][0];
                                    $response['correct_answer'] = $choices[$correct_answer_index];
                                }
                                if (isset($submission['result']['response'])) {
                                    $h5p_response = $submission['result']['response'];
                                    $counts[$h5p_response]++;
                                    $response['counts'] = $counts;
                                }
                                break;
                            case('true-false'):
                                if (!$choices) {
                                    $choices = ['True', 'False'];
                                    $counts = [0, 0];
                                    $correct_answer_index = $object['definition']['correctResponsesPattern'][0] ? 0 : 1;
                                    $response['correct_answer'] = $choices[$correct_answer_index];
                                }
                                if (isset($submission['result']['response'])) {
                                    $submission['result']['response'] === "true" ? $counts[0]++ : $counts[1]++;
                                    $response['counts'] = $counts;
                                }
                                break;
                        }
                        //Log::info(print_r($submission['result'], true));

                        break;
                    default:
                        $response['message'] = 'Only h5p is supported at this time.';
                        return $response;
                }
            }

            $response['pie_chart_data']['labels'] = $choices;
            $response['pie_chart_data']['datasets']['borderWidth'] = 1;
            foreach ($choices as $key => $choice) {
                $percent = 90 - 10 * $key;
                $first = 197 - 20 * $key;
                $response['pie_chart_data']['datasets']['backgroundColor'][$key] = "hsla($first, 85%, ${percent}%, 0.9)";
            }
            $total = array_sum($counts);
            if ($total) {
                foreach ($counts as $key => $count) {
                    $counts[$key] = Round(100 * $count / $total);
                }
            }
            $response['pie_chart_data']['datasets']['data'] = $counts;
            $number_submission_results = count($submission_results); //don't include Fake
            $response['response_percent'] = $number_enrolled ? Round(100 *  $number_submission_results  / $number_enrolled, 1) : 0;
            $response['type'] = 'success';


        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the submissions.  Please refresh the page and try again or contact us for assistance.";
        }
        return $response;
    }

    public function getChoices($technology, $object)
    {
        $choices = [];
        switch ($technology) {
            case('h5p'):
                foreach ($object['choices'] as $key => $choice) {
                    $choices[] = array_values($choice['description'])[0];
                }
                break;

        }
        return $choices;
    }

    public function exploredLearningTree(Assignment $assignment, Question $question, Submission $submission)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $question->id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $submission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', Auth::user()->id)
                ->update(['explored_learning_tree' => 1]);
            $response['type'] = 'success';
            $response['explored_learning_tree'] = true;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating that you explored the Learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
