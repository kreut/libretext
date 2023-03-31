<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentQuestionTimeOnTask;
use App\Enrollment;
use App\Exceptions\Handler;
use App\Question;
use App\Submission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AssignmentQuestionTimeOnTaskController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $Submission
     * @return array
     * @throws Exception
     */
    public function update(Request    $request,
                           Assignment $assignment,
                           Question   $question,
                           Submission $Submission): array
    {
        $response['type'] = 'error';
        try {
            $authorized = Gate::inspect('store', [$Submission, $assignment, $assignment->id, $question->id]);
           if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $time_on_task = DB::table('assignment_question_time_on_tasks')
                ->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            $current_time_on_task = $time_on_task ? $time_on_task->time_on_task : 0;
            AssignmentQuestionTimeOnTask::updateOrCreate(
                ['user_id' => $request->user()->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question->id],
                ['time_on_task' => $current_time_on_task + $request->time_on_task]
            );

            $submission = $Submission->where('user_id', $request->user()->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();
            if ($submission) {
                $submission->time_on_task = $submission->time_on_task + $request->time_on_task;
                $submission->save();
            }
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();

        }
        return $response;
    }


}
