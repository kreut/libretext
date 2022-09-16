<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\ReviewHistory;
use App\SubmissionHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StudentHistoryController extends Controller
{
    public function store(Request           $request,
                          Assignment        $assignment,
                          Question          $question,
                          ReviewHistory     $reviewHistory,
                          SubmissionHistory $submissionHistory)
    {
        try {
            //TODO: Authorized by being enrolled in the class

            $assign_to_timing = $assignment->assignToTimingByUser();

            if (time() > strtotime($assign_to_timing->due)) {
                $reviewHistory->user_id = $request->user();
                $reviewHistory->assignment_id = $assignment->id;
                $reviewHistory->question_id = $question->id;
                $reviewHistory->save();
                session()->put('review_history', $reviewHistory->id);
            } else {
                $submissionHistory->user_id = $request->user();
                $submissionHistory->assignment_id = $assignment->id;
                $submissionHistory->question_id = $question->id;
                $submissionHistory->save();
                session()->put('submission_history', $submissionHistory->id);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }
    }

    public function update(Request           $request,
                           Assignment        $assignment,
                           Question          $question,
                           ReviewHistory     $reviewHistory,
                           SubmissionHistory $submissionHistory)
    {
        try {
            //TODO: Authorized by being enrolled in the class

            $assign_to_timing = $assignment->assignToTimingByUser();

            if (time() > strtotime($assign_to_timing->due)) {
                $reviewHistory->user_id = $request->user();
                $reviewHistory->assignment_id = $assignment->id;
                $reviewHistory->question_id = $question->id;
                $reviewHistory->save();
                session()->put('review_history', $reviewHistory->id);
            } else {
                $submissionHistory->user_id = $request->user();
                $submissionHistory->assignment_id = $assignment->id;
                $submissionHistory->question_id = $question->id;
                $submissionHistory->save();
                session()->put('submission_history', $submissionHistory->id);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

        }


    }
}
