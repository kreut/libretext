<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use \Exception;
use App\Extension;
use App\SubmissionText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use App\Traits\GeneralSubmissionPolicy;
use App\Traits\DateFormatter;


class SubmissionTextController extends Controller
{
    use GeneralSubmissionPolicy;

    use DateFormatter;

    public function storeSubmissionText(Request $request, Extension $extension, SubmissionText $submissionText)
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $assignment = Assignment::find($assignment_id);
        $user = Auth::user();

        $authorized = Gate::inspect('storeSubmissionText', [$submissionText, $assignment]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            //validator put here to be consistent with the file submissions

            if ($can_submit_text_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment->id, $question_id)) {
                if ($can_submit_text_response['type'] === 'error') {
                    $response['message'] = $can_submit_text_response['message'];
                    return $response;
                }
            }
            if ($request->text_submission === '') {

                $response['message'] = "You did not submit anything.";
                return $response;
            }
            $now = Carbon::now();
            $submission_text_data = [
                'submission' => $request->text_submission,
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'date_submitted' => Carbon::now()];

            $submissionText->updateOrCreate(
                ['user_id' => $user->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question_id],
                $submission_text_data
            );

            $response['type'] = 'success';
            $response['message'] = 'Your text submission was saved.';
            $response['last_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($now,
                $user->time_zone, 'M d, Y g:i:s a');
            $log = new \App\Log();
            $request->action = 'submit-question-text';
            $request->data = ['assignment_id' => $assignment->id,
                'question_id' => $question_id];
            $log->store($request);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your text submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
