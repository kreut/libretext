<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use App\Http\Requests\SubmissionScoreOverrideRequest;
use App\SubmissionScoreOverride;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmissionScoreOverrideController extends Controller
{
    /**
     * @param SubmissionScoreOverrideRequest $request
     * @param SubmissionScoreOverride $submissionScoreOverride
     * @return array
     * @throws Exception
     */
    public function update(SubmissionScoreOverrideRequest $request,
                           SubmissionScoreOverride        $submissionScoreOverride): array
    {
        $response['type'] = 'error';
        $assignment_id = $request->assignment_id;
        $question_id = $request->question_id;
        $student_user_id = $request->student_user_id;
        $authorized = Gate::inspect('update', [$submissionScoreOverride, $assignment_id, $question_id, $student_user_id]
        );

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $submissionScoreOverride->updateOrCreate(
                ['assignment_id' => $assignment_id,
                    'question_id' => $question_id,
                    'user_id' => $student_user_id],
                ['score' => $data['score']]
            );
            $response['message'] = "The score for $request->first_last on question $request->question_title has been updated.";
            $response['type'] = 'success';
            DB::ccommit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error overriding this score.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
