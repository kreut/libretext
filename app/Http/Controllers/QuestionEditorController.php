<?php

namespace App\Http\Controllers;


use App\AssignToTiming;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\MyFavorite;
use App\Question;
use App\QuestionEditor;
use App\SavedQuestionsFolder;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionEditorController extends Controller
{
    /**
     * @param Request $request
     * @param QuestionEditor $questionEditor
     * @param User $user
     * @return array
     * @throws Exception
     */
    public
    function index(Request $request, QuestionEditor $questionEditor, User $user): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('index', $questionEditor);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $question_editors_info = $user->where('role', 5)
                ->select('id',
                    'email',
                    'created_at',
                    DB::raw('CONCAT(first_name, " ", last_name) AS name'))
                ->get();
            $question_editors = [];
            foreach ($question_editors_info as $key => $question_editor_info) {
                $question_editors[$key] = $question_editor_info;
                $question_editors[$key]->is_default_non_instructor_editor = Helper::defaultNonInstructorEditor()->id === $question_editor_info->id;
            }
            $response['question_editors'] = $question_editors;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the non-instructor editors.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param QuestionEditor $questionEditor
     * @param User $questionEditorUser
     * @param Question $question
     * @param MyFavorite $myFavorite
     * @param SavedQuestionsFolder $savedQuestionsFolder
     * @param AssignToTiming $assignToTiming
     * @return array
     * @throws Exception
     */
    public
    function destroy(QuestionEditor       $questionEditor,
                     User                 $questionEditorUser,
                     Question             $question,
                     MyFavorite           $myFavorite,
                     SavedQuestionsFolder $savedQuestionsFolder,
                     AssignToTiming       $assignToTiming): array
    {

        $response['type'] = 'error';

        $authorized = Gate::inspect('destroy', [$questionEditor, $questionEditorUser]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $default_question_editor_user = Helper::defaultNonInstructorEditor();
            DB::beginTransaction();
            $question->where('question_editor_user_id', $questionEditorUser->id)
                ->update(['question_editor_user_id' => $default_question_editor_user->id,
                    'public' => 1]);
            $saved_question_folders = $savedQuestionsFolder->where('user_id', $questionEditorUser->id)
                ->where('type', 'my_questions')->get();
            foreach ($saved_question_folders as $saved_question_folder) {
                $saved_question_folder->name = "$saved_question_folder->name ( $questionEditorUser->first_name  $questionEditorUser->last_name)";
                $saved_question_folder->user_id = $default_question_editor_user->id;
                $saved_question_folder->save();
            }
            $myFavorite->where('user_id', $questionEditorUser->id)->delete();
            $savedQuestionsFolder->where('user_id', $questionEditorUser->id)->delete();
            DB::table('can_give_ups')->where('user_id', $questionEditorUser->id)->delete();//issue with database change
            DB::table('seeds')->where('user_id', $questionEditorUser->id)->delete();
            $courses = Course::where('user_id', $questionEditorUser->id)->get();
            foreach ($courses as $course) {
                $course->user_id = $default_question_editor_user->id;
                $course->name = "$course->name ( $questionEditorUser->first_name  $questionEditorUser->last_name)";
                $course->save();
                foreach ($course->assignments as $assignment) {
                    DB::table('seeds')
                        ->where('user_id', $questionEditorUser->id)
                        ->where('assignment_id', $assignment->id)
                        ->delete();
                }
            }

            $questionEditorUser->delete();
            $response['message'] = "$questionEditorUser->first_name $questionEditorUser->last_name has been removed and all of their questions and courses have been moved to the Default Question Editor.";
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the non-instructor editor.  Please try again or contact us for assistance.";
        }
        return $response;

    }


}
