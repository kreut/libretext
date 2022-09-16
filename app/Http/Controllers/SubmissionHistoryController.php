<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\SubmissionHistory;
use Exception;
use Illuminate\Http\Request;

class SubmissionHistoryController extends Controller
{
    public function store(Request           $request,
                          Assignment        $assignment,
                          Question          $question,
                          SubmissionHistory $submissionHistory)
    {
        try {
            //TODO: Authorized by being enrolled in the class, can submit

            $submissionHistory->user_id = $request->user();
            $submissionHistory->assignment_id = $assignment->id;
            $submissionHistory->question_id = $question->id;
            $submissionHistory->save();
            session()->put('submission_history', $submissionHistory->id);

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }
    }

}
