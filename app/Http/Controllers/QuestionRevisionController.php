<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\QuestionRevision;
use App\Traits\DateFormatter;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionRevisionController extends Controller
{
    use DateFormatter;

    /**
     * @param QuestionRevision $questionRevision
     * @param Assignment $assignment
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function getUpdateRevisionInfo(QuestionRevision $questionRevision, Assignment $assignment, Question $question): array
    {
        $response['type'] = 'error';
        try {

            $authorized = Gate::inspect('getUpdateRevisionInfo', $questionRevision);
            if (!$authorized->allowed()) {
                $response['message'] = "You are not allowed to get the revision for this question.";
                return $response;
            }
            $updates = [];
            foreach ($questionRevision as $key => $value) {
                if (!in_array($key, ['created_at', 'updated_at', 'id', 'revision_number', 'action', 'reason_for_edit'])) {
                    if ($questionRevision->{$key} !== $question->{$key}) {
                        $updates[$key] = ['current' => $questionRevision->{$key}, 'updated' => $question->{$key}];
                    }
                }
            }

            $response['type'] = 'success';
            $response['updates'] = $updates;
            $response['reason_for_edit'] = $questionRevision->reason_for_edit;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the information needed to update the question revision. Please try again.";
        }
        return $response;
    }

    /**
     * @param Question $question
     * @param QuestionRevision $questionRevision
     * @return array
     * @throws Exception
     */
    public function getRevisionsByQuestion(Question $question, QuestionRevision $questionRevision): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('update', [$question, $question->folder_id]);
            if (!$authorized->allowed()) {
                $response['message'] = "You are not allowed to get the revisions for this question.";
                return $response;
            }
            $revision_info = DB::table('question_revisions')
                ->join('users', 'question_revisions.question_editor_user_id', '=', 'users.id')
                ->where('question_id', $question->id)
                ->orderBy('updated_at', 'desc')
                ->select('question_revisions.*',
                    DB::raw('CONCAT(first_name, " " , last_name) AS question_editor'))
                ->get();
            $revisions = [];
            foreach ($revision_info as $key => $revision) {
                $additional_text = '';
                if ($key === 0){
                    $additional_text = ' (Current)';
                }
                if ($key === count($revision_info) - 1){
                    $additional_text = ' (Original)';
                }
                $text = $revision->revision_number . ' --- ';
                $text .= Carbon::parse($revision->updated_at)->timezone(request()->user()->time_zone)->format('n/j/y g:i:s A');
                $text .= ' --- ' . $revision->question_editor;
                $text .= $additional_text;
                $revision->text = $text;
                $revisions[] = $revision;
            }
            $response['type'] = 'success';
            $response['revisions'] = $revisions;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the question revisions. Please try again.";

        }
        return $response;
    }

    /**
     * @param Request $request
     * @param QuestionRevision $questionRevision
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function show(Request $request, QuestionRevision $questionRevision, Question $question): array
    {
        $response['type'] = 'error';
        try {

            $authorized = Gate::inspect('show', $questionRevision);
            if (!$authorized->allowed()) {
                $response['message'] = "You are not allowed to get the revision for this question.";
                return $response;
            }
            $question_revision_id = $questionRevision->id;
            $question_revision = $question->formatQuestionToEdit($request, $questionRevision, $questionRevision->question_id);
            $question_revision['id'] = $questionRevision->question_id;
            $question_revision['question_revision_id'] = $question_revision_id;

            $question_revision['tags'] = [];
            $response['type'] = 'success';
            $response['question_revision'] = $question_revision;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the question revision. Please try again.";

        }
        return $response;

    }
}
