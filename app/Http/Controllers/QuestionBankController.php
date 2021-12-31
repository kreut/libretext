<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionBankController extends Controller
{
    public
    function getQuestionsWithCourseLevelUsageInfo(Request $request)
    {
        $response['type'] = 'error';
        $userAssignment = Assignment::find($request->user_assignment_id);
        switch ($request->collection_type) {
            case('assignment'):
                $assignment = Assignment::find($request->assignment_id);
                $authorized = Gate::inspect('getQuestionsWithCourseLevelUsageInfo', $assignment);
                $table = 'assignment_question';
                $potential_questions_query =
                    DB::table('assignment_question')
                        ->join('questions', "assignment_question.question_id", '=', 'questions.id')
                        ->where('assignment_id', $assignment->id)
                        ->orderBy('order');
                break;
            case('saved-questions'):
                $table = 'saved_questions';
                $potential_questions_query =
                     DB::table('saved_questions')
                        ->join('questions', "saved_questions.question_id", '=', 'questions.id')
                        ->where('user_id', $request->user()->id)
                        ->where('folder_id', $request->folder_id)
                        ->orderBy('updated_at', 'desc');
                break;
            default:
                $response['message'] = "$request->collection_type is not a valid collection type.";
                return $response;
        }


        try {
            $question_in_assignment_information = $userAssignment->questionInAssignmentInformation();
            $saved_questions = DB::table('saved_questions')
                ->join('saved_questions_folders', 'saved_questions.folder_id', '=', 'saved_questions_folders.id')
                ->where('saved_questions.user_id', request()->user()->id)
                ->select('question_id', 'folder_id', 'name')
                ->get();
            $saved_questions_by_question_id = [];
            foreach ($saved_questions as $saved_question) {
                $saved_questions_by_question_id[$saved_question->question_id] = [
                    'folder_id' => $saved_question->folder_id,
                    'name' => $saved_question->name];

            }
            //Get all assignment questions Question Upload, Solution, Number of Points
            $potential_questions = $potential_questions_query->select("$table.*",
                'questions.title',
                'questions.id AS question_id',
                'questions.technology_iframe',
                'questions.technology')
                ->get();

            $question_ids = [];
            foreach ($potential_questions as  $assignment_question) {
                $question_ids[] = $assignment_question->question_id;

            }

            $tags = DB::table('question_tag')->whereIn('question_id', $question_ids)
                ->join('tags', 'question_tag.tag_id', '=', 'tags.id')
                ->select('question_id', 'tag')
                ->get();
            $tags_by_question_id = [];
            foreach ($tags as $tag) {
                if (!isset($tags_by_question_id[$tag->question_id])) {
                    $tags_by_question_id[$tag->question_id] = [];
                }
                $tags_by_question_id[$tag->question_id][] = $tag->tag;

            }
            foreach ($potential_questions as $key => $assignment_question) {
                $potential_questions[$key]->submission = Helper::getSubmissionType($assignment_question);
                $potential_questions[$key]->in_assignment = $question_in_assignment_information[$assignment_question->question_id] ?? false;
                if (isset($saved_questions_by_question_id[$assignment_question->question_id])) {
                    $potential_questions[$key]->saved_questions_folder_id = $saved_questions_by_question_id[$assignment_question->question_id]['folder_id'];
                    $potential_questions[$key]->saved_questions_folder_name = $saved_questions_by_question_id[$assignment_question->question_id]['name'];
                }
                $potential_questions[$key]->tags = isset($tags_by_question_id[$assignment_question->question_id]) ? implode(', ', $tags_by_question_id[$assignment_question->question_id]) : 'none';
            }
            $response['assignment_questions'] = $potential_questions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the questions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

}
