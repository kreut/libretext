<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use Illuminate\Http\Request;
use App\Assignment;
use App\Question;
use App\AssignmentSyncQuestion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class AssignmentSyncQuestionController extends Controller
{
    public function getQuestionIdsByAssignment(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            $response['question_ids'] = json_encode($assignment->questions()->pluck('question_id'));//need to do since it's an array
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function store(Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('add', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment->questions()->syncWithoutDetaching($question);
            $response['type'] = 'success';
            $response['message'] = 'The question has been added to the assignment.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error adding the question to the assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function destroy(Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('delete', [$assignmentSyncQuestion, $assignment]);


        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $assignment->questions()->detach($question);
            $response['type'] = 'success';
            $response['message'] = 'The question has been removed from the assignment.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the question from the assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public function getQuestionsToView(Assignment $assignment)
    {


        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['type'] = 'success';
            foreach ($assignment->questions as $question) {
                echo $question->id . "<br>";
            }

            foreach ($assignment->questions as $key => $question) {
                $custom_claims = ['Adapt' => ['user_id' => Auth::user()->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];
                $assignment->questions[$key]->jwt = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
            }
            $response['questions'] = $assignment->questions;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
