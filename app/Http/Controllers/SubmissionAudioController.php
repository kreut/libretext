<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentFile;
use App\Exceptions\Handler;
use App\User;
use \Exception;
use App\Extension;
use App\Question;
use App\SubmissionFile;
use App\Traits\LatePolicy;
use App\Traits\S3;
use App\Traits\GeneralSubmissionPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use App\Traits\DateFormatter;

class SubmissionAudioController extends Controller
{
    use DateFormatter;
    use LatePolicy;
    use S3;
    use GeneralSubmissionPolicy;

    public function logError(Request $request)
    {
        Log::error(print_r($request->all()));

    }

    public function store(Request $request, Assignment $assignment, Question $question, SubmissionFile $submissionFile, Extension $extension)
    {
        $response['type'] = 'error';
        $assignment_id = $assignment->id;
        $question_id = $question->id;
        $user = $request->user();
        $user_id = $user->id;


        if ($can_upload_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id)) {
            if ($can_upload_response['type'] === 'error') {
                $response['message'] = $can_upload_response['message'];
                return $response;
            }
        }

        try {
            $max_number_of_uploads_allowed = 15;//number allowed per question/assignment
            $latest_submission = DB::table('submission_files')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $user_id)
                ->select('upload_count')
                ->first();

            $upload_count = is_null($latest_submission) ? 0 : $latest_submission->upload_count;


            if ($upload_count + 1 > $max_number_of_uploads_allowed) {
                $response['message'] = 'You have exceeded the number of times that you can re-upload a file submission.';
                return $response;

            }

            $submission = $request->file('audio')->store("assignments/$assignment->id", 'local');
            $submissionContents = Storage::disk('local')->get($submission);
            Storage::disk('s3')->put($submission, $submissionContents, ['StorageClass' => 'STANDARD_IA']);


            $submission_file_data = ['type' => 'audio',
                'submission' => basename($submission),
                'original_filename' => '',
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'upload_count' => $upload_count + 1,
                'date_submitted' => Carbon::now()];
            DB::beginTransaction();

            $submissionFile->updateOrCreate(
                ['user_id' => $user_id,
                    'assignment_id' => $assignment_id,
                    'question_id' => $question_id],
                $submission_file_data
            );


            $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime(date('Y-m-d H:i:s'), Auth::user()->time_zone, 'M d, Y g:i:s a');
            $response['submission_file_url'] =$this->getTemporaryUrl($assignment_id, basename($submission));
            $response['message'] = "Your audio submission has been saved.";
            $response['late_file_submission'] = $this->isLateSubmission($extension, $assignment, Carbon::now());


            if (($upload_count >= $max_number_of_uploads_allowed - 3)) {
                $response['message'] .= "  You may resubmit " . ($max_number_of_uploads_allowed - (1 + $upload_count)) . " more times.";
            }
            $response['type'] = 'success';
            $log = new \App\Log();
            $request->action = 'submit-question-audio';
            $request->data = ['assignment_id' => $assignment_id,
                'question_id' => $question_id];
            $log->store($request);
            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve the audio submissions for this assignment.  Please try again or contact us for assistance.";
        }
        return $response;
    }


    public function storeAudioFeedback(Request $request, User $user, Assignment $assignment, Question $question, AssignmentFile $assignmentFile)
    {

        $response['type'] = 'error';
        $assignment_id = $assignment->id;
        $question_id = $question->id;
        $student_user_id = $user->id;


         $authorized = Gate::inspect('uploadAudioFeedback', [$assignmentFile, $user->find($student_user_id), $assignment->find($assignment_id)]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {


            //save locally and to S3
            $audioFeedback = $request->file('audio')->store("assignments/$assignment_id", 'local');
            $feedbackContents = Storage::disk('local')->get($audioFeedback);
            Storage::disk('s3')->put($audioFeedback, $feedbackContents, ['StorageClass' => 'STANDARD_IA']);

            DB::table('submission_files')
                ->where('user_id', $student_user_id)
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->update(['file_feedback' => basename($audioFeedback)]);

            $response['type'] = 'success';
            $response['message'] = 'Your audio feedback has been saved.';
            $response['file_feedback_url'] = $this->getTemporaryUrl($assignment_id, basename($audioFeedback));
$response['file_feedback_type'] = 'audio';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your audio feedback.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
