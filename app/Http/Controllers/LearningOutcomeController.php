<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\LearningOutcome;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LearningOutcomeController extends Controller
{
    /**
     * @param LearningOutcome $learningOutcome
     * @param string $subject
     * @return array
     * @throws Exception
     */
    public function getLearningOutcomes(LearningOutcome $learningOutcome, string $subject): array
    {

        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('getLearningOutcomes', $learningOutcome);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $response['learning_outcomes'] = DB::table('learning_outcomes')
                ->select('id', 'topic', 'description')
                ->where('subject', $subject)
                ->get();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the learning outcomes.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;
    }


    public function getDefaultSubject(LearningOutcome $learningOutcome): array
    {

        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('getDefaultSubject', $learningOutcome);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $learning_outcome = DB::table('questions')
                ->join('question_learning_outcome', 'questions.id','=','question_learning_outcome.question_id')
                ->join('learning_outcomes', 'question_learning_outcome.learning_outcome_id', '=', 'learning_outcomes.id')
                ->where('question_editor_user_id',request()->user()->id)
                ->orderBy('questions.id', 'desc')
                ->first();

            $response['default_subject'] = $learning_outcome ? $learning_outcome->subject : null;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the default subject.  Please try again or contact us for assistance.";
            return $response;
        }
        return $response;
    }


}
