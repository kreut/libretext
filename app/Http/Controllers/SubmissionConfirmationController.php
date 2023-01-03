<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\Submission;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SubmissionConfirmationController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param Submission $submission
     * @return array
     * @throws Exception
     */
    public function store(Request $request,
                          Assignment $assignment,
                          Question $question,
                          Submission $submission): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$submission, $assignment, $assignment->id, $question->id]);
        if (!$authorized->allowed()) {
            $response['message'] = 'Not authorized to store a submission confirmation.';
            return $response;
        }
        try {
            DB::table('submission_confirmations')->insert([
                'user_id' => $request->user()->id,
                'assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'created_at' => now(),
                'updated_at' => now()]);

            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
$response['message'] = $e->getMessage();
        }
        return $response;
    }
}
