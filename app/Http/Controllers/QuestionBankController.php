<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{
    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public
    function getQuestionsWithCourseLevelUsageInfo(Request $request)
    {

        $response['type'] = 'error';
        $userAssignment = Assignment::find($request->user_assignment_id);
        switch ($request->collection_type) {
            case('assignment'):
                $assignment_ids = [];
                if ($request->course_id) {
                    $assignment_ids = Course::find($request->course_id)->assignments->pluck('id')->toArray();
                }
                if ($request->assignment_id) {
                    $assignment_ids = [$request->assignment_id];
                }
                if (!$request->course_id && !$request->assignment_id) {
                    $response['message'] = 'Information missing in request.';
                    return $response;
                }
                //  $assignment = Assignment::find($request->assignment_id);
                // $authorized = Gate::inspect('getQuestionsWithCourseLevelUsageInfo', $assignment);
                $table = 'assignment_question';
                $potential_questions_query =
                    DB::table('assignment_question')
                        ->join('questions', "assignment_question.question_id", '=', 'questions.id')
                        ->join('assignments', 'assignment_question.assignment_id', '=', 'assignments.id')
                        ->whereIn('assignment_id', $assignment_ids)
                        ->orderBy('assignments.order')
                        ->orderBy('assignment_question.order');
                break;
            case('my_favorites'):
                $table = 'my_favorites';
                $folder_ids =[ $request->folder_id];
                if ( $request->folder_id === 'all_folders'){
                    $folder_ids = DB::table('my_favorites')->where('user_id', $request->user()->id)
                        ->select('folder_id')
                        ->pluck('folder_id')
                        ->toArray();
                }
                $potential_questions_query =
                    DB::table('my_favorites')
                        ->join('questions', "my_favorites.question_id", '=', 'questions.id')
                        ->where('user_id', $request->user()->id)
                        ->whereIn('my_favorites.folder_id', $folder_ids)
                        ->orderBy('updated_at', 'desc');
                break;
            case('my_questions'):
                $table = 'questions';
                $folder_ids =[ $request->folder_id];
                if ( $request->folder_id === 'all_folders'){
                    $folder_ids = DB::table('questions')
                        ->where('question_editor_user_id', $request->user()->id)
                        ->select('folder_id')
                        ->groupBy('folder_id')
                        ->pluck('folder_id')
                        ->toArray();
                }
                $potential_questions_query =
                    DB::table('questions')
                        ->where('question_editor_user_id', $request->user()->id)
                        ->whereIn('folder_id',$folder_ids)
                        ->orderBy('updated_at', 'desc');
                break;
            default:
                $response['message'] = "$request->collection_type is not a valid collection type.";
                return $response;
        }


        try {
            $question_in_assignment_information = $userAssignment->questionInAssignmentInformation();
            $my_favorites = DB::table('my_favorites')
                ->join('saved_questions_folders', 'my_favorites.folder_id', '=', 'saved_questions_folders.id')
                ->where('my_favorites.user_id', request()->user()->id)
                ->select('question_id', 'folder_id', 'name')
                ->get();
            $my_favorites_by_question_id = [];
            foreach ($my_favorites as $my_favorite) {
                $my_favorites_by_question_id[$my_favorite->question_id] = [
                    'folder_id' => $my_favorite->folder_id,
                    'name' => $my_favorite->name];

            }
            //Get all assignment questions Question Upload, Solution, Number of Points
            $potential_questions = $potential_questions_query->select("$table.*",
                'questions.title',
                'questions.id AS question_id',
                'questions.technology_iframe',
                'questions.technology',
                'questions.text_question',
                'questions.library',
                'questions.page_id')
                ->get();

            $question_ids = [];
            foreach ($potential_questions as $assignment_question) {
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
            $efs_dir = '/mnt/local/';
            $is_efs = is_dir($efs_dir);
            $storage_path = $is_efs
                ? $efs_dir
                : Storage::disk('local')->getAdapter()->getPathPrefix();


            foreach ($potential_questions as $key => $assignment_question) {
                $potential_questions[$key]->submission = Helper::getSubmissionType($assignment_question);
                $potential_questions[$key]->in_assignment = $question_in_assignment_information[$assignment_question->question_id] ?? false;
                $non_technology_text_file = "$storage_path$assignment_question->library/$assignment_question->page_id.php";
                if (file_exists($non_technology_text_file)) {
                    //add this for searching
                    $potential_questions[$key]->text_question .= file_get_contents($non_technology_text_file);
                }
                if (isset($my_favorites_by_question_id[$assignment_question->question_id])) {
                    $potential_questions[$key]->my_favorites_folder_id = $my_favorites_by_question_id[$assignment_question->question_id]['folder_id'];
                    $potential_questions[$key]->my_favorites_folder_name = $my_favorites_by_question_id[$assignment_question->question_id]['name'];
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
