<?php

namespace App\Http\Controllers;

use App\CurrentQuestionEditor;
use App\Exceptions\Handler;
use App\Question;
use App\Traits\DateFormatter;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CurrentQuestionEditorController extends Controller
{
    use DateFormatter;

    public function update(Request               $request,
                           Question              $question,
                           CurrentQuestionEditor $currentQuestionEditor): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $currentQuestionEditor);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            CurrentQuestionEditor::updateOrCreate(
                ['user_id' => $request->user()->id,
                    'question_id' => $question->id],
                ['updated_at' => now()]
            );
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to update who was currently editing this question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param CurrentQuestionEditor $currentQuestionEditor
     * @return array
     * @throws Exception
     */
    public function show(Request               $request,
                         Question              $question,
                         CurrentQuestionEditor $currentQuestionEditor): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('show', $currentQuestionEditor);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $last_24_hours = Carbon::now()->subDay()->format('Y-m-d H:i:s');
            $current_question_editor = DB::table('current_question_editors')
                ->select(DB::raw('CONCAT(first_name, " ", last_name) AS name'),
                    'current_question_editors.user_id', 'current_question_editors.created_at',
                    'time_zone')
                ->join('users', 'current_question_editors.user_id', '=', 'users.id')
                ->where('question_id', $question->id)
                ->where('current_question_editors.user_id', '<>', $request->user()->id)
                ->where('current_question_editors.created_at', '>', $last_24_hours)
                ->orderBy('current_question_editors.id', 'DESC')
                ->first();
            if ($current_question_editor) {
                $start_time = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($current_question_editor->created_at, $request->user()->time_zone, 'F jS \a\t g:i a');
                $current_question_editor = "$current_question_editor->name began editing this question on $start_time. Please hold off on editing the question until they have completed their work.";
            }
            $response['current_question_editor'] = $current_question_editor;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to see who was currently editing this question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Question $question
     * @param CurrentQuestionEditor $currentQuestionEditor
     * @return array
     * @throws Exception
     */
    public function destroy(Request               $request,
                            Question              $question,
                            CurrentQuestionEditor $currentQuestionEditor): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', [$currentQuestionEditor, $question]);


        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $currentQuestionEditor->where('user_id', $request->user()->id)
                ->where('question_id', $question->id)
                ->delete();

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to remove who was editing this question.  Please try again or contact us for assistance.";
        }

        return $response;
    }

}
