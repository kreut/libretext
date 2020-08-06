<?php

namespace App\Policies;

use App\User;
use App\Submission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the question in the assignment.
     *
     * @param \App\User $user
     * @param \App\Assignment $assignment
     * @return mixed
     */
    public function store(User $user, Submission $submission, $assignment, int $assignment_id, int $question_id)
    {
        //question belongs to assignment in a course that they're enrolled in
        $question_in_assignment = DB::table('assignment_question')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->get()
            ->isNotEmpty();

        //not past the due date for an extension
        if (!$question_in_assignment) {
            return Response::deny('No responses will be saved since that question is not in the assignment.');
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            return Response::deny('No responses will be saved since the assignment is not part of your course.');
        }

        $assignment_is_past_due = DB::table('assignments')
            ->where('id', $assignment_id)
            ->where('due', '<', DB::raw('NOW()'))
            ->get()
            ->isNotEmpty();

        if ($assignment_is_past_due) {
            $extension = DB::table('extensions')
                ->select(DB::raw('UNIX_TIMESTAMP(extension) as extension'))
                ->where('assignment_id', $assignment_id)
                ->where('user_id', $user->id)
                ->first('extension');
            if ($extension) {
                if ($extension->extension < time()) {
                    return Response::deny('No responses will be saved since your extension for this assignment has passed.');
                }
            } else {
                return Response::deny('No responses will be saved since the due date for this assignment has passed.');
            }
            $assignment_not_yet_available = DB::table('assignments')
                ->where('id', $assignment_id)
                ->where('available_from', '>', DB::raw('NOW()'))
                ->get()
                ->isNotEmpty();

            if ($assignment_not_yet_available) {
                return Response::deny('No responses will be saved since this assignment is not yet available.');
            }

        }
        return Response::allow();
    }
}
