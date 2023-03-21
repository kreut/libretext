<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\DataShop;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Http\Requests\StoreSubmission;
use App\Question;
use App\Score;
use App\Submission;
use App\UnconfirmedSubmission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnconfirmedSubmissionController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param UnconfirmedSubmission $unconfirmedSubmission
     * @return array
     * @throws Exception
     */
    public function show(Request $request, Assignment $assignment, Question $question, UnconfirmedSubmission $unconfirmedSubmission): array
    {
        $response['type'] = 'error';
        try {
            $unconfirmed_submission = $unconfirmedSubmission->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->where('user_id', $request->user()->id)
                ->first();
            if (!$unconfirmed_submission) {
                $response['message'] = "There is no existing submission to confirm.";
                return $response;
            }
            $formatted_unconfirmed_submission = [];
            $submission_info = json_decode($unconfirmed_submission->submission, 1);
            if ($submission_info && isset($submission_info['submission'])
                && isset($submission_info['submission']['score'])
                && isset($submission_info['submission']['score']['answers'])) {
                foreach ($submission_info['submission']['score']['answers'] as $value) {
                    $formatted_submission =  $value['preview_latex_string']
                        ? '\(' . $value['preview_latex_string'] . '\)'
                        : $value['original_student_ans'];
                    $formatted_unconfirmed_submission[] = $formatted_submission;
                }
            }
            $response['unconfirmed_submission'] = $formatted_unconfirmed_submission;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to retrieve your unconfirmed submission.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function storeSubmission(Request $request, Assignment $assignment, Question $question, UnconfirmedSubmission $unconfirmedSubmission): array
    {
        $response['type'] = 'error';
        $unconfirmed_submission = $unconfirmedSubmission->where('user_id', $request->user()->id)
            ->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->first()
        ->toArray();


        try {
            if (!$unconfirmed_submission) {
                $response['message'] = "We were not able to find that unconfirmed submission.  Please try again or contact us for assistance.";
                return $response;
            }
            $unconfirmed_submission['submission']=  json_decode($unconfirmed_submission['submission'],1)['submission'];
            $unconfirmed_submission['technology'] = 'webwork';
            $Submission = new Submission();
            return $Submission->store(new StoreSubmission($unconfirmed_submission),
                new Submission(),
                new Assignment(),
                new Score(),
                new DataShop(),
                new AssignmentSyncQuestion());
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "We were not able to save this submission.  Please try again or contact us for assistance.";
        }
        return $response;

    }
}
