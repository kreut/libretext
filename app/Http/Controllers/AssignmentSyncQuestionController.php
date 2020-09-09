<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;

use Illuminate\Http\Request;
use App\Http\Requests\updateAssignmentQuestionPointsRequest;
use App\Assignment;
use App\Question;

use App\Traits\iframeFormatter;
use App\AssignmentSyncQuestion;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AssignmentSyncQuestionController extends Controller
{

    use IframeFormatter;
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

    public function getQuestionInfoByAssignment(Assignment $assignment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['questions'] = [];
            $assignment_question_info = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->get();
            if ($assignment_question_info->isNotEmpty()) {
                foreach ($assignment_question_info as $question_info) {
                    //for getQuestionsByAssignment (internal)
                    $response['questions'][$question_info->question_id] = $question_info;
                    //for the axios call from questions.get.vue
                    $response['question_ids'][] = $question_info->question_id;
                    if ($question_info->question_files) {
                        $response['question_files'][] = $question_info->question_id;
                    }

                }
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the assignment questions.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function toggleQuestionFiles(Request $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['question_files' => $request->question_files]);
            $response['type'] = $request->question_files ? 'success' : 'info';
            $response['message'] = $request->question_files ? 'Your students can now upload a question file for this question.'
                : 'Your student can no longer upload a question file for this question.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error toggling the file upload option.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function updatePoints(updateAssignmentQuestionPointsRequest $request, Assignment $assignment, Question $question, AssignmentSyncQuestion $assignmentSyncQuestion)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', [$assignmentSyncQuestion, $assignment]);
        $data = $request->validated();

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            DB::table('assignment_question')->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->update(['points' => $request->points]);
            $response['type'] = 'success';
            $response['message'] = 'The number of points have been updated.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the number of points.  Please try again or contact us for assistance.";
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
            DB::table('assignment_question')
                ->insert([
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'points' => $assignment->default_points_per_question //don't need to test since tested already when creating an assignment
                ]);
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


            $assignment_question_info = $this->getQuestionInfoByAssignment($assignment);

            $question_ids = [];
            $question_files = [];
            $points = [];
            if (!$assignment_question_info['questions']){
                $response['questions'] = [];
                return $response;
            }
            foreach ($assignment_question_info['questions'] as $question) {
                $question_ids[$question->question_id] = $question->question_id;
                $question_files[$question->question_id] = $question->question_files;
                $points[$question->question_id] = $question->points;
            }


            $instructor_user_id = $assignment->course->user_id;
            $instructor_learning_trees = DB::table('learning_trees')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', $instructor_user_id)
                ->get();
            $instructor_learning_trees_by_question_id = [];
            $other_instructor_learning_trees_by_question_id = [];

            if ($instructor_learning_trees) {
                foreach ($instructor_learning_trees as $key => $value) {
                    $instructor_learning_trees_by_question_id[$value->question_id] = json_decode($value->learning_tree)->blocks;
                }
            }
            $other_instructor_learning_trees = DB::table('learning_trees')
                ->whereIn('question_id', $question_ids)
                ->where('user_id', '<>', Auth::user()->id)
                ->get();
            //just get the first one created

            if ($other_instructor_learning_trees) {
                foreach ($other_instructor_learning_trees as $key => $value) {
                    $other_instructor_learning_trees_by_question_id[$value->question_id] = json_decode($value->learning_tree)->blocks;
                }
            }

            foreach ($assignment->questions as $key => $question) {
                $assignment->questions[$key]['points'] = $points[$question->id];
                $assignment->questions[$key]['questionFiles'] = $question_files[$question->id];//camel case because using in vue
                $custom_claims = ['adapt' => [
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'technology' => $question->technology]];
                $custom_claims["{$question->technology}"] = '';
                if ($question->technology === 'webwork') {
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
                $problemJWT = \JWTAuth::customClaims($custom_claims)->fromUser(Auth::user());
                $assignment->questions[$key]->body = $this->formatIframe($question['body'], $problemJWT);

                if (isset($instructor_learning_trees_by_question_id[$question->id])) {
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
