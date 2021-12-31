<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\MyFavorite;
use App\SavedQuestionsFolder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MyFavoriteController extends Controller
{

    /**
     * @param Assignment $assignment
     * @param MyFavorite $myFavorite
     * @return array
     * @throws Exception
     */
    public function getMyFavoriteQuestionIdsByCommonsAssignment(Assignment $assignment, MyFavorite $myFavorite): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getMyFavoriteQuestionIdsByCommonsAssignment', [$myFavorite, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $question_ids = $assignment->questions->pluck('id')->toArray();
        try {
            $my_favorites_questions = DB::table('my_favorites')
                ->join('assignment_question', 'my_favorites.question_id', '=', 'assignment_question.question_id')
                ->where('my_favorites.user_id', request()->user()->id)
                ->whereIn('my_favorites.question_id', $question_ids)
                ->select('my_favorites.question_id AS my_favorites_question_id', 'folder_id AS my_favorites_folder_id')
                ->get();

            $response['my_favorite_questions'] = $my_favorites_questions;
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
     * @param MyFavorite $myFavorite
     * @return array
     * @throws Exception
     */
    public
    function store(Request $request, MyFavorite $myFavorite): array
    {
        $response['type'] = 'error';
        $folder_id = $request->folder_id;
        $question_ids = $request->question_ids;
        $assignment_id = $request->chosen_assignment_id;
        foreach ($question_ids as $question_id) {
            $authorized = Gate::inspect('store', [$myFavorite, $assignment_id, $question_id, $folder_id]);
            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
        }

        try {
            DB::beginTransaction();
            foreach ($question_ids as $question_id) {
                $learning_tree_id = null;
                $assignment_question = DB::table('assignment_question')
                    ->where('assignment_id', $assignment_id)
                    ->where('question_id', $question_id)
                    ->first();
                if ($assignment_question) {
                    $learning_tree_exists = DB::table('assignment_question_learning_tree')
                        ->where('assignment_question_id', $assignment_question->id)
                        ->first();
                    if ( $learning_tree_exists){
                        $learning_tree_id =  $learning_tree_exists->id;
                    }

                }

                $myFavorite = new MyFavorite();

                if (!$myFavorite->where('user_id', request()->user()->id)
                    ->where('question_id', $question_id)
                    ->first()) {
                    $myFavorite->user_id = request()->user()->id;
                    $myFavorite->folder_id = $folder_id;
                    $myFavorite->question_id = $question_id;
                    $myFavorite->open_ended_submission_type = $assignment_question ? $assignment_question->open_ended_submission_type : 0;
                    $myFavorite->open_ended_text_editor = $assignment_question ? $assignment_question->open_ended_text_editor : null;
                    $myFavorite->learning_tree_id = $learning_tree_id;
                    $myFavorite->save();
                }
            }
            DB::commit();
            $response['type'] = 'success';
            $response['message'] = 'The question has been added to your My Favorites Folder.';
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
     * @param MyFavorite $myFavorite
     * @return array
     * @throws Exception
     */
    public
    function destroy(SavedQuestionsFolder $savedQuestionsFolder,
                     Question $question,
                     MyFavorite $myFavorite): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', [$myFavorite, $question, $savedQuestionsFolder]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $myFavorite->where('user_id', request()->user()->id)
                ->where('question_id', $question->id)
                ->where('folder_id', $savedQuestionsFolder->id)
                ->delete();
            $response['type'] = 'info';
            $response['message'] = 'The question has been removed from your favorites.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error removing the question from your favorites.  Please try again or contact us for assistance.";
        }

        return $response;
    }


}
