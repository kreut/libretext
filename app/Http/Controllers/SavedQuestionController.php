<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\SavedQuestion;
use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SavedQuestionController extends Controller
{

    public function getSavedQuestionsWithCourseLevelUsageInfo(Assignment $assignment, SavedQuestion $savedQuestion): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getSavedQuestionsWithCourseLevelUsageInfo', [$savedQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $question_in_assignment_information = $assignment->questionInAssignmentInformation();

            //Get all assignment questions Question Upload, Solution, Number of Points
            $saved_questions = DB::table('saved_questions')
                ->join('questions', 'saved_questions.question_id', '=', 'questions.id')
                ->where('saved_questions.user_id', request()->user()->id)
                ->select('saved_questions.open_ended_submission_type',
                    'saved_questions.open_ended_text_editor',
                    'saved_questions.learning_tree_id',
                    'questions.title',
                    'questions.id AS question_id',
                    'questions.technology_iframe',
                    'questions.technology')
                ->get();
            $response['type'] = 'success';
            foreach ($saved_questions as $key => $assignment_question) {
                $saved_questions[$key]->submission = Helper::getSubmissionType($assignment_question);
                $saved_questions[$key]->in_assignment = $question_in_assignment_information[$assignment_question->question_id] ?? false;
            }
            $response['saved_questions'] = $saved_questions;

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
        $question_ids = $assignment->questions->pluck('id')->toArray();
        try {
            $saved_question_ids = [];
            $saved_questions = DB::table('saved_questions')
                ->join('assignment_question', 'saved_questions.question_id', '=', 'assignment_question.question_id')
                ->where('saved_questions.user_id', request()->user()->id)
                ->whereIn('saved_questions.question_id', $question_ids)
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
        $folder_id = $request->folder_id;
        $authorized = Gate::inspect('store', [$savedQuestion, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $question_ids = $request->question_ids;
            DB::beginTransaction();
            foreach ($question_ids as $question_id) {
                $assignment_question = DB::table('assignment_question')
                    ->where('assignment_id', $assignment->id)
                    ->where('question_id', $question_id)
                    ->first();
                $learning_tree_exists = DB::table('assignment_question_learning_tree')
                    ->where('assignment_question_id', $assignment_question->id)
                    ->first();
                $learning_tree_id = $learning_tree_exists ? $learning_tree_exists->id : null;
                if (!$assignment_question) {
                    $response['message'] = "Assessment with ADAPT ID $question_id no longer exists.  Please refresh your page for an updated view of the assessments.";
                    return $response;
                }

                $savedQuestion = new SavedQuestion();

                if (!$savedQuestion->where('user_id', request()->user()->id)
                    ->where('question_id', $assignment_question->question_id)
                    ->first()) {
                    $savedQuestion->user_id = request()->user()->id;
                    $savedQuestion->folder_id = $folder_id;
                    $savedQuestion->question_id = $assignment_question->question_id;
                    $savedQuestion->open_ended_submission_type = $assignment_question->open_ended_submission_type;
                    $savedQuestion->open_ended_text_editor = $assignment_question->open_ended_text_editor;
                    $savedQuestion->learning_tree_id = $learning_tree_id;
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
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param Question $question
     * @param SavedQuestion $savedQuestion
     * @return array
     * @throws Exception
     */
    public
    function destroy(SavedQuestionsFolder $savedQuestionsFolder, Question $question, SavedQuestion $savedQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', [$savedQuestion, $question, $savedQuestionsFolder]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $savedQuestion->where('user_id', request()->user()->id)
                ->where('question_id', $question->id)
                ->where('folder_id', $savedQuestionsFolder->id)
                ->delete();
            $response['type'] = 'info';
            $response['message'] = 'The question has been removed from your saved list.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the saved question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    public
    function move(Question $question,
                  SavedQuestionsFolder $fromFolder,
                  SavedQuestionsFolder $toFolder,
                  SavedQuestion $savedQuestion): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('move', [$savedQuestion, $question, $fromFolder, $toFolder]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            DB::beginTransaction();
            //remove it from the new folder to avoid duplicates
            $savedQuestion->where('question_id', $question->id)
                ->where('folder_id', $toFolder->id)
                ->delete();
           $savedQuestion->where('question_id', $question->id)
                ->where('folder_id', $fromFolder->id)
                ->update(['folder_id' => $toFolder->id]);

            $response['type'] = 'info';
            $response['message'] = "The question $question->title has been moved from $fromFolder->name to $toFolder->name.";
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the saved question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

}
