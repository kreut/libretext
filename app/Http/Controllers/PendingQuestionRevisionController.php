<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingQuestionRevisionController extends Controller
{
    /**
     * @param QuestionRevision $questionRevision
     * @return array
     * @throws Exception
     */
    public function show(QuestionRevision $questionRevision): array
    {
        $response['type'] = 'error';

        try {
            $question_editor = DB::table('users')
                ->where('id', $questionRevision->question_editor_user_id)
                ->select('id AS question_editor_user_id', DB::raw('CONCAT(first_name, " " , last_name) AS question_editor_name'))
                ->first();
            $questionRevision->question_editor_name = $question_editor->question_editor_name;

            $rubric_categories = DB::table('rubric_categories')
                ->where('question_revision_id', $questionRevision->id)
                ->get()
                ->toArray();
            $questionRevision->rubric_categories = $rubric_categories;
            $response['type'] = 'success';
            $response['question_revision'] = $questionRevision;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to get your question revision.  Please try again or contact support for assistance.";
        }
        return $response;
    }

}
