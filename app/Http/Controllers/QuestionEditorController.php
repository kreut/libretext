<?php

namespace App\Http\Controllers;


use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Question;
use App\QuestionEditor;
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
            $question_editors = $user->where('role', 5)
                ->select('id',
                    'email',
                    'created_at',
                    DB::raw('CONCAT(first_name, " ", last_name) AS name'))
                ->get();
            $response['question_editors'] = $question_editors;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the non-instructor editors.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    public
    function destroy(QuestionEditor $questionEditor, User $questionEditorUser, Question $question)
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
            $questionEditorUser->delete();
            $response['message'] = "$questionEditorUser->first_name $questionEditorUser->last_name has been removed and all of their questions have been moved to the Default Question Editor.";
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
