<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentTopic;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\QuestionBank;
use App\SavedQuestionsFolder;
use App\Tag;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class QuestionBankController extends Controller
{

    public function showDescriptionsCookie(Request $request)
    {
        $cookie = ($request->hasCookie('show_descriptions') === false || $request->cookie('show_descriptions') === false)
            ? cookie()->forever('show_descriptions', 1)
            : cookie()->forever('show_descriptions', 0);
        $response['type'] = 'success';
        return response($response)->withCookie($cookie);
    }


    /**
     * @param Request $request
     * @param QuestionBank $questionBank
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public
    function getQuestionsWithCourseLevelUsageInfo(Request      $request,
                                                  QuestionBank $questionBank,
                                                  Question     $question): array
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
                $folder_name = '';
                if ($request->folder_id === 'all_folders') {
                    $folder_name = 'All questions';
                } else if (is_int($request->folder_id)) {
                    $folder = DB::table('saved_questions_folders')->where('id', $request->folder_id)->first();
                    if ($folder) {
                        $folder_name = $folder->name;
                    }
                }
                if (in_array($folder_name, ['H5P Imports', 'All questions'])) {
                    //$question->autoImportH5PQuestions();
                }
                $folder_ids = [$request->folder_id];
                if ($request->folder_id === 'all_folders') {
                    $folder_ids = DB::table('questions')
                        ->where('question_editor_user_id', $request->user()->id)
                        ->select('folder_id')
                        ->groupBy('folder_id')
                        ->pluck('folder_id')
                        ->toArray();
                }
                $per_page = $request->per_page;
                $current_page = $request->current_page;
                $filter = $request->filter;
                //don't show the question if they haven't been edited yet
                $empty_learning_tree_nodes = DB::table('empty_learning_tree_nodes')
                    ->select('question_id')
                    ->get()
                    ->pluck('question_id')
                    ->toArray();
                $question_ids =
                    DB::table('questions')
                        ->where('question_editor_user_id', $request->user()->id)
                        ->whereIn('folder_id', $folder_ids)
                        ->whereNotIn('id', $empty_learning_tree_nodes);

                if ($filter) {
                    $question_ids = $question_ids->where(function ($query) use ($filter) {
                        $query->where('title', "like", "%$filter%")
                            ->orWhere('id', 'like', "%$filter%");
                    });

                }
                $question_ids = $question_ids->orderBy('id', 'desc')
                    ->skip($per_page * ($current_page - 1))
                    ->take($per_page)
                    ->get()
                    ->sortBy('id')
                    ->pluck('id')
                    ->toArray();

                $potential_questions_query = DB::table('questions')
                    ->where('question_editor_user_id', $request->user()->id)
                    ->whereIn('folder_id', $folder_ids)
                    ->whereIn('id', $question_ids)
                    ->orderBy('id', 'desc');


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
                    'questions.description',
                    'questions.id AS question_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.technology_id',
                    'questions.text_question',
                    'questions.library',
                    'questions.page_id',
                    'questions.qti_json',
                    'assignment_topics.name AS topic')
                    ->get()
                : $potential_questions_query->select("$table.*",
                    'questions.title',
                    'questions.description',
                    'questions.id AS question_id',
                    'questions.technology_iframe',
                    'questions.technology',
                    'questions.technology_id',
                    'questions.qti_json',
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
                    QuestionBank $questionBank,
                    Tag          $Tag): array
    {
        $per_page = $request->per_page;
        $current_page = $request->current_page;


        $author = $request->author;
        $title = $request->title;
        $question_content = $request->question_content;
        $technology = $request->technology;
        $webwork_content_type = $request->webwork_content_type;
        $webwork_algorithmic = $request->webwork_algorithmic;
        $question_type = $request->question_type;
        $qti_question_type = $request->qti_question_type;
        $technology_id = $technology !== 'any' ? $request->technology_id : null;
        $question_id = $request->question_id;
        if (($pos = strpos($question_id, "-")) !== false) {
            $question_id = substr($question_id, $pos + 1);
        }
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
                ->where('version', 1);
            if ($request->user()->role === 5) {
                $non_instructor_user_ids = DB::table('users')->where('role', 5)->get('id')->pluck('id')->toArray();
                $question_ids = $question_ids->whereIn('question_editor_user_id', $non_instructor_user_ids);
                if ($request->course_id) {
                    if ($request->assignment_id) {
                        $question_ids = $question_ids->whereIn('id', Assignment::find($request->assignment_id)->questions->pluck('id')->toArray());
                    } else {
                        $assignment_ids = Course::find($request->course_id)->assignments->pluck('id')->toArray();
                        $course_question_ids = DB::table('assignment_question')->whereIn('assignment_id', $assignment_ids)->get('question_id')->pluck('question_id')->toArray();
                        $question_ids = $question_ids->whereIn('id', $course_question_ids);
                    }
                }
            } else {
                if (!$request->user()->isMe()) {
                    $question_ids = $question_ids->where(function ($query) use ($request) {
                        $query->where('public', '=', 1)
                            ->orWhere('question_editor_user_id', '=', $request->user()->id);
                    });
                }

            }
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
                if ($technology === 'qti') {
                    $basic_types = ['multiple_choice', 'true_false', 'numerical', 'multiple_answers', 'fill_in_the_blank', 'select_choice', 'matching'];
                    switch ($qti_question_type) {
                        case('basic'):
                            $question_ids = $question_ids->whereIn('qti_json_type', $basic_types);
                            break;
                        case('nursing'):
                            $question_ids = $question_ids->whereNotIn('qti_json_type', $basic_types);
                            break;
                        default:
                            break;
                    }
                }
                if ($technology === 'webwork') {
                    switch ($webwork_content_type) {
                        case('pgml'):
                            $question_ids = $question_ids->where('webwork_code', 'LIKE', "%BEGIN_PGML%");
                            break;
                        case('pl'):
                            $question_ids = $question_ids->where(function ($query) {
                                $query->where('webwork_code', 'NOT LIKE', "%BEGIN_PGML%")
                                    ->orWhereNull('webwork_code');
                            });
                            break;
                        default:
                            break;
                    }
                }
                switch ($webwork_algorithmic) {
                    case('algorithmic only'):
                        $question_ids = $question_ids->where('webwork_code', 'LIKE', "%random(%");
                        break;
                    case('non-algorithmic only'):
                        $question_ids = $question_ids->where(function ($query) {
                            $query->where('webwork_code', 'NOT LIKE', "%random(%")
                                ->orWhereNull('webwork_code');
                        });
                        break;
                    default:
                        break;
                }
            }
            if ($question_type !== 'any') {
                $question_ids = $question_ids->where('question_type', $question_type);
            }
            if ($question_content === 'auto_graded_only') {
                $question_ids = $question_ids->where('technology', '<>', 'text');
            }
            if ($question_content === 'open_ended_only') {
                $question_ids = $question_ids->where('technology', '=', 'text');
            }

            if ($question_id) {
                $question_ids = $question_ids->where('id', $question_id);
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
                ->select(
                    'id AS question_id',
                    'id',
                    DB::raw('CONCAT(library, "-", page_id) AS library_page_id'),
                    'library',
                    'page_id',
                    'title',
                    'description',
                    'author',
                    'technology',
                    'question_type',
                    'qti_json',
                    'qti_json_type',
                    'h5p_type',
                    'technology_id',
                    'non_technology')
                ->whereIn('id', $question_ids)
                ->get();


            $tags = $Tag->getUsableTags($question_ids);
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
            if (!$questions) {
                $response['message'] = $question_id ? "There are no public questions with ADAPT ID $question_id."
                    : "There are no questions matching that your search parameters.";
                return $response;
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
