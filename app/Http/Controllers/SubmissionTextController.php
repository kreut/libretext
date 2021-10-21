<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentLevelOverride;
use App\AssignmentSyncQuestion;
use App\Exceptions\Handler;
use App\Question;
use App\QuestionLevelOverride;
use App\Score;
use App\Submission;
use \Exception;
use App\SubmissionFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

use App\Traits\GeneralSubmissionPolicy;
use App\Traits\SubmissionFiles;
use App\Traits\DateFormatter;
use Illuminate\Support\Facades\Storage;


class SubmissionTextController extends Controller
{
    use GeneralSubmissionPolicy;

    use DateFormatter;
    use SubmissionFiles;


    public function destroy(Request $request, Assignment $assignment, Question $question, Submission $submission, SubmissionFile $submissionFile)
    {
        $response['type'] = 'error';
        $assignment = Assignment::find($assignment->id);
        $user = Auth::user();
        $authorized = Gate::inspect('delete', [$submission, $assignment, $assignment->id, $question->id]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            //validator put here to be consistent with the file submissions

            $submissionFile->where('user_id', $user->id)
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->delete();

            $response['type'] = 'success';
            $response['message'] = 'Your submission was removed.';
            $response['date_submitted'] = 'N/A';

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save your text submission.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param SubmissionFile $submissionFile
     * @param Submission $submission
     * @param AssignmentSyncQuestion $assignmentSyncQuestion
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function store(Request                $request,
                          SubmissionFile         $submissionFile,
                          Submission             $submission,
                          AssignmentSyncQuestion $assignmentSyncQuestion,
                          Score                  $score)
    {

        $response['type'] = 'error';
        $assignment_id = $request->assignmentId;
        $question_id = $request->questionId;
        $assignment = Assignment::find($assignment_id);
        $user = Auth::user();
        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment_id, $question_id]);
        if (!$authorized->allowed()) {
            $questionLevelOverride = new QuestionLevelOverride();
            $assignmentLevelOverride = new AssignmentLevelOverride();
            $has_question_level_override = $questionLevelOverride->hasOpenEndedOverride($assignment_id, $question_id, $assignmentLevelOverride);
            if (!$has_question_level_override) {
                $response['message'] = $authorized->message();
                return $response;
            }
        }
        try {
            //validator put here to be consistent with the file submissions

            if ($can_submit_text_response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment->id, $question_id)) {
                if ($can_submit_text_response['type'] === 'error') {
                    $questionLevelOverride = new QuestionLevelOverride();
                    $assignmentLevelOverride = new AssignmentLevelOverride();
                    $has_question_level_override = $questionLevelOverride->hasOpenEndedOverride($assignment_id, $question_id, $assignmentLevelOverride);
                    if (!$has_question_level_override) {
                        $response['message'] = $can_submit_text_response['message'];
                        return $response;
                    }
                }
            }
            if (!$request->text_submission) {
                $response['message'] = "You did not submit any text.";
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


            $filename = md5(uniqid('', true)) . '.html';
            $file_path = "assignments/{$assignment_id}/$filename";
            Storage::disk('local')->put($file_path, $request->text_submission);
            Storage::disk('s3')->put($file_path, $request->text_submission, ['StorageClass' => 'STANDARD_IA']);


            $submission_text_data = [
                'original_filename' => '',
                'submission' => $filename,
                'type' => 'text',
                'file_feedback' => null,
                'text_feedback' => null,
                'date_graded' => null,
                'score' => null,
                'upload_count' => $upload_count,
                'date_submitted' => Carbon::now()];
            DB::beginTransaction();
            $submissionFile->updateOrCreate(
                ['user_id' => $user->id,
                    'assignment_id' => $assignment->id,
                    'question_id' => $question_id],
                $submission_text_data
            );
            $score = $this->updateScoreIfCompletedScoringType($assignment, $question_id);
            $response['type'] = 'success';
            $response['message'] = 'Your text submission was saved.';
            $response['score'] = $score === null ? null : $score;
            $response['completed_all_assignment_questions'] = $assignmentSyncQuestion->completedAllAssignmentQuestions($assignment);
            $response['date_submitted'] = $this->convertUTCMysqlFormattedDateToHumanReadableLocalDateAndTime($now,
                $user->time_zone, 'M d, Y g:i:s a');
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
