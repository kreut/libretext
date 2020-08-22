<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;

use Illuminate\Http\Request;
use App\Assignment;
use App\Question;
use App\AssignmentSyncQuestion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


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


$question_ids = json_decode($this->getQuestionIdsByAssignment($assignment)['question_ids'], true);


$instructor_user_id = $assignment->course->user_id;
         $instructor_learning_trees = DB::table('learning_trees')
                            ->whereIn('question_id', $question_ids)
                            ->where('user_id', $instructor_user_id )
                            ->get();
         $instructor_learning_trees_by_question_id = [];
         $other_instructor_learning_trees_by_question_id = [];

         if ($instructor_learning_trees) {
             foreach ($instructor_learning_trees as $key=>$value){
                 $instructor_learning_trees_by_question_id[$value->question_id]= json_decode($value->learning_tree)->blocks;
             }
         }
            $other_instructor_learning_trees = DB::table('learning_trees')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', '<>',Auth::user()->id)
                ->get();
         //just get the first one created

            if ($other_instructor_learning_trees ) {
                    foreach ($other_instructor_learning_trees as $key=>$value){
                        $other_instructor_learning_trees_by_question_id[$value->question_id]= json_decode($value->learning_tree)->blocks;
                    }
            }

            foreach ($assignment->questions as $key => $question) {
                $custom_claims = ['adapt' => [
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];
                $custom_claims["{$question->technology}"] = '';
                if ($question->technology === 'webwork'){
                    $custom_claims['webwork'] = [];
                    $custom_claims['webwork']['problemSeed'] = '1234567';
                    $custom_claims['webwork']['courseID'] = 'daemon_course';
                    $custom_claims['webwork']['userID'] = 'daemon';
                    $custom_claims['webwork']['course_password'] = 'daemon';
                    $custom_claims['webwork']['showSummary'] = 1;
                    $custom_claims['webwork']['displayMode'] = 'MathJax';
                    $custom_claims['webwork']['language'] = 'en';
                    $custom_claims['webwork']['outputformat'] = 'libretexts';
                }
                $assignment->questions[$key]->token = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
                if (isset($instructor_learning_trees_by_question_id[$question->id])){
                    $assignment->questions[$key]->learning_tree = $instructor_learning_trees_by_question_id[$question->id];
                } elseif (isset($other_instrutor_learning_trees_by_question_id[$question->id])) {
                    $assignment->questions[$key]->learning_tree = $other_instructor_learning_trees_by_question_id[$question->id];
                } else {
                    $assignment->questions[$key]->learning_tree = '';
                }
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
