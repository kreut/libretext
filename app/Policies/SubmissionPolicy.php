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

        if (!$assignment->questions->contains($question_id)) {
            return Response::deny('No responses will be saved since that question is not in the assignment.');
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            return Response::deny('No responses will be saved since the assignment is not part of your course.');
        }

        if (strtotime($assignment->available_from) > time()) {
            return Response::deny('No responses will be saved since this assignment is not yet available.');
        }

        $db_raw_unix_timestamp = (env('DB_CONNECTION') === 'mysql') ? 'UNIX_TIMESTAMP(extension) as extension' : "strftime('%s', extension) as extension";
        if (time() > strtotime($assignment->due)) {
            $extension = DB::table('extensions')
                ->select(DB::raw($db_raw_unix_timestamp))
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

        }


        return Response::allow();
    }
}
