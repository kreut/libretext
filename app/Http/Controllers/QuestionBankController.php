<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentTopic;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\QuestionBank;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{
    /**
     * @param Request $request
     * @param QuestionBank $questionBank
     * @return array
     * @throws Exception
     */
    public
    function getQuestionsWithCourseLevelUsageInfo(Request      $request,
                                                  QuestionBank $questionBank): array
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
                        ->leftJoin('assignment_topics', 'assignment_question.assignment_topic_id', '=', 'assignment_topics.id')
                        ->whereIn('assignment_question.assignment_id', $assignment_ids);

                if ($request->topic_id) {
                    $potential_questions_query = $potential_questions_query->where('assignment_topic_id', $request->topic_id);
                }

                $potential_questions_query = $potential_questions_query
                    ->orderBy('assignments.order')
                    ->orderBy('assignment_question.order');
                break;
            case ('my_favorites'):
                $table = 'my_favorites';
                $folder_ids = [$request->folder_id];
                if ($request->folder_id === 'all_folders') {
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
                $folder_ids = [$request->folder_id];
                if ($request->folder_id === 'all_folders') {
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
                        ->whereIn('folder_id', $folder_ids)
                        ->orderBy('updated_at', 'desc');
                break;
            default:
                $response['message'] = "$request->collection_type is not a valid collection type.";
                return $response;
        }


        try {


//Get all assignment questions Question Upload, Solution, Number of Points
//dd($request->all());
            $potential_questions = !$request->topic_id && $request->assignment_id
                ? $potential_questions_query->select("$table.*",
                    'questions.title',
                    'questions.id AS question_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.technology_id',
                    'questions.text_question',
                    'questions.library',
                    'questions.page_id',
                    'assignment_topics.name AS topic')
                    ->get()
                : $potential_questions_query->select("$table.*",
                    'questions.title',
                    'questions.id AS question_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.technology_id',
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
            $potential_questions = $questionBank->getSupplementaryQuestionInfo($potential_questions, $userAssignment, ['tags', 'text_question'], $tags_by_question_id);
            $response['assignment_questions'] = $potential_questions;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the questions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function getAll(Request      $request,
                    Question     $question,
                    QuestionBank $questionBank): array
    {
        $per_page = $request->per_page;
        $current_page = $request->current_page;


        $author = $request->author;
        $title = $request->title;
        $question_type = $request->question_type;
        $technology = $request->technology;
        $technology_id = $technology !== 'any' ? $request->technology_id : null;
        $tags = explode(',', $request->tags);
        foreach ($tags as $key => $tag) {
            $tags[$key] = trim($tag);
        }


        $response['type'] = 'error';
        $userAssignment = Assignment::find($request->user_assignment_id);
        $authorized = Gate::inspect('index', $question);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $question_ids = DB::table('questions')
                ->select('id')
                ->where('version', 1)
                ->where(function ($query) use ($request) {
                    $query->where('public', '=', 1)
                        ->orWhere('question_editor_user_id', '=', $request->user()->id);
                });

            if ($request->tags) {
                $question_ids_with_tags = DB::table('tags')
                    ->join('question_tag', 'tags.id', '=', 'question_tag.tag_id')
                    ->where(function ($query) use ($tags) {
                        foreach ($tags as $tag) {
                            $query->orwhere('tag', 'like', '%' . $tag . '%');
                        }
                    })
                    ->select('question_id')
                    ->get()
                    ->pluck('question_id')
                    ->toArray();
                $question_ids = $question_ids->whereIn('questions.id', $question_ids_with_tags);
            }
            if ($title) {
                $question_ids = $question_ids->where('title', 'LIKE', "%$title%");
            }
            if ($author) {
                $question_ids = $question_ids->where('author', 'LIKE', "%$author%");
            }
            if ($technology !== 'any') {
                $question_ids = $question_ids->where('technology', $technology);
                if ($technology_id) {
                    $question_ids = $question_ids->where('technology_id', $technology_id);
                }
            }
            if ($question_type === 'auto_graded_only') {
                $question_ids = $question_ids->where('technology', '<>', 'text');
            }
            if ($question_type === 'open_ended_only') {
                $question_ids = $question_ids->where('technology', '=', 'text');
            }

            $total_rows = $question_ids->count();

            $question_ids = $question_ids->orderBy('id')
                ->skip($per_page * ($current_page - 1))
                ->take($per_page)
                ->get()
                ->sortBy('id')
                ->pluck('id')
                ->toArray();

            $questions_info = DB::table('questions')
                ->select('id AS question_id',
                    DB::raw('CONCAT(library, "-", page_id) AS library_page_id'),
                    'title',
                    'author',
                    'technology',
                    'technology_id')
                ->whereIn('id', $question_ids)
                ->get();


            $tags = DB::table('tags')
                ->join('question_tag', 'tags.id', '=', 'question_tag.tag_id')
                ->select('tag', 'question_id')
                ->whereIn('question_id', $question_ids)
                ->where('tag', '<>', 'article:topic')
                ->where('tag', '<>', 'showtoc:no')
                ->where('tag', 'NOT LIKE', '%path-library/%')
                ->get();
            $tags_by_question_id = [];
            foreach ($tags as $tag) {
                if (!isset($tags_by_question_id[$tag->question_id])) {
                    $tags_by_question_id[$tag->question_id] = [];
                }
                $tags_by_question_id[$tag->question_id][] = $tag->tag;
            }
            $questions = [];
            $questions_info = $questionBank->getSupplementaryQuestionInfo($questions_info, $userAssignment);
            foreach ($questions_info as $key => $value) {
                $questions[$key] = $value;
                if (!$value->technology_id) {
                    $questions[$key]->technology_id = 'None';
                } else {
                    $questions[$key]->technology_id = is_numeric($value->technology_id)
                        ? $value->technology_id
                        : chunk_split($value->technology_id, 30, '<br>');
                }
                $questions[$key]->tag = isset($tags_by_question_id[$value->question_id])
                    ? implode(', ', $tags_by_question_id[$value->question_id])
                    : 'None';
                $questions[$key]->author = $value->author ?: 'None';
            }

            $response['all_questions'] = $questions;
            $response['total_rows'] = $total_rows;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve your questions.  Please try again or contact us for assistance.";
        }

        return $response;

    }

}
