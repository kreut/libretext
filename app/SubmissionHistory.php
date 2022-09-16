<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubmissionHistory extends Model
{
    public function store($user_id, $assignment_id, $question_id)
    {
        $this->user_id = $user_id;
        $this->assignment_id = $assignment_id;
        $this->question_id = $question_id;
        $this->save();
        session()->put('submission_history', $this->id);
    }

    public function updateSubmissionHistory($submission)
    {

        ////use the updated last info function????
        ///
        $submissionHistory = SubmissionHistory::find(session()->get('submission_history'));
        if ($submissionHistory) {
            $submissionHistory->submission = $submission;
            $submissionHistory->save();
        }

    }
}
