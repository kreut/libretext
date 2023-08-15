<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\SubmissionScoreOverrideRequest;
use App\Score;
use App\SubmissionScoreOverride;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmissionScoreOverrideController extends Controller
{
    /**
     * @param SubmissionScoreOverrideRequest $request
     * @param SubmissionScoreOverride $submissionScoreOverride
     * @param Score $score
     * @return array
     * @throws Exception
     */
    public function update(SubmissionScoreOverrideRequest $request,
                           SubmissionScoreOverride        $submissionScoreOverride,
                           Score                          $score): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->student_user_id;
        $authorized = Gate::inspect('update', [$submissionScoreOverride, $assignment_id, $student_user_id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $submission = DB::table('submissions')
                ->where('assignment_id', $assignment_id)
                ->where('user_id', $student_user_id)
                ->where('question_id', $question_id)
                ->first();
            $submission_file = DB::table('submission_files')
                ->where('assignment_id', $assignment_id)
                ->where('user_id', $student_user_id)
                ->where('question_id', $question_id)
                ->first();
            $original_score = 0;
            $original_score+= $submission ? $submission->score : 0;
            $original_score += $submission_file ? $submission_file->score : 0;
            floatval($original_score) === floatval($data['score'])
                ? $submissionScoreOverride->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $student_user_id)
                ->delete()
                : $submissionScoreOverride->updateOrCreate(
                    ['assignment_id' => $assignment_id,
                        'question_id' => $question_id,
                        'user_id' => $student_user_id],
                    ['score' => $data['score']]
                );

            $score->updateAssignmentScore($student_user_id, $assignment_id);
            $response['message'] = "The score for $request->first_last on question $request->question_title has been updated.";
            $response['type'] = 'success';
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error overriding this score.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
