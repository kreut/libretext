<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\SavedQuestion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SavedQuestionController extends Controller
{

    public function index(SavedQuestion $savedQuestion): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $savedQuestion);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


            try {

                //Get all assignment questions Question Upload, Solution, Number of Points
                $assignment_questions = DB::table('saved_questions')
                ->join('assignment_question','saved_questions.assignment_question_id','=','assignment_question.id')
                    ->join('questions', 'assignment_question.question_id', '=', 'questions.id')
                    ->where('saved_questions.user_id', request()->user()->id)
                    ->select('assignment_question.*',
                        'questions.title',
                        'questions.id AS question_id',
                        'questions.technology_iframe',
                        'questions.technology')
                    ->get();
                $response['type'] = 'success';
                foreach ($assignment_questions as $key => $assignment_question) {
                    $assignment_questions[$key]->submission = Helper::getSubmissionType($assignment_question);
                }
                $response['assignment_questions'] = $assignment_questions;

            } catch (Exception $e) {
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "There was an error getting the questions for this assignment.  Please try again or contact us for assistance.";
            }
            return $response;
    }

    /**
     * @param Assignment $assignment
     * @param SavedQuestion $savedQuestion
     * @return array
     * @throws Exception
     */
    public function getSavedQuestionIdsByAssignment(Assignment $assignment, SavedQuestion $savedQuestion): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getSavedQuestionIdsByAssignment', [$savedQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $saved_question_ids = [];
            $saved_questions = DB::table('saved_questions')
                ->join('assignment_question', 'saved_questions.assignment_question_id', '=', 'assignment_question.id')
                ->where('saved_questions.user_id', request()->user()->id)
                ->get();
            if ($saved_questions) {
                foreach ($saved_questions as $saved_question) {
                    $saved_question_ids[] = $saved_question->question_id;
                }
            }
            $response['saved_question_ids'] = $saved_question_ids;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the saved questions for this assignment.  Please try again or contact us for assistance.";


        }
        return $response;


    }

    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param SavedQuestion $savedQuestion
     * @return array
     * @throws Exception
     */
    public
    function store(Request $request, Assignment $assignment, SavedQuestion $savedQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$savedQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $question_ids = $request->question_ids;
            DB::beginTransaction();
            foreach ($question_ids as $question_id) {
                $assignment_question = DB::table('assignment_question')->where('assignment_id', $assignment->id)
                    ->where('question_id', $question_id)
                    ->first();
                if (!$assignment_question) {
                    $response['message'] = "Assessment with Adapt ID $question_id no longer exists.  Please refresh your page for an updated view of the assessments.";
                    return $response;
                }

                $savedQuestion = new SavedQuestion();

                if (!$savedQuestion->where('user_id', request()->user()->id)
                    ->where('assignment_question_id', $assignment_question->id)
                    ->first()) {
                    $savedQuestion->user_id = request()->user()->id;
                    $savedQuestion->assignment_question_id = $assignment_question->id;
                    $savedQuestion->save();
                }
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'Your newly saved questions can now be imported into one of your assignments.';
        } catch (Exception $e) {
            DB::rollBack();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the questions.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * @param Assignment $assignment
     * @param Question $question
     * @param SavedQuestion $savedQuestion
     * @return array
     * @throws Exception
     */
    public
    function destroy(Assignment $assignment, Question $question, SavedQuestion $savedQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', [$savedQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if ($assignment_question) {
                $savedQuestion->where('user_id', request()->user()->id)
                    ->where('assignment_question_id', $assignment_question->id)
                    ->delete();
            }

            $response['type'] = 'info';
            $response['message'] = 'The question has been removed from your saved list.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the saved question.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
