<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Submission;
use \Exception;
use App\SubmissionFile;
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

    public function storeSubmissionText(Request $request, SubmissionFile $submissionFile, Submission $submission)
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $assignment = Assignment::find($assignment_id);
        $user = Auth::user();

        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment_id, $question_id]);
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
            $latest_submission = DB::table('submission_files')
                ->where('type', 'text') //not needed but for completeness
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', Auth::user()->id)
                ->select('upload_count')
                ->first();
            $upload_count = is_null($latest_submission) ? 0 : $latest_submission->upload_count;
            $submission_text_data = [
                'original_filename' => '',
                'submission' => $request->text_submission,
                'type' => 'text',
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'upload_count' => $upload_count,
                'date_submitted' => Carbon::now()];
            $submissionFile->updateOrCreate(
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
