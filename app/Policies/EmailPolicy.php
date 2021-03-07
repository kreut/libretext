<?php

namespace App\Policies;

use App\User;
use App\Email;
use App\SubmissionFile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EmailPolicy
{
    use HandlesAuthorization;


    public function contactGrader(User $user, Email $email, int $to_user_id, int $assignment_id, int $question_id)
    {
        $has_access = false;
        //student can send to grader
        if ($user->role === 3) {
//get the courses for which they're enrolled and let them send the email if it's a grader or instructor in the course
            $has_access = SubmissionFile::where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $user->id)
                ->where('grader_id', $to_user_id)
                ->get()
                ->isNotEmpty();
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to send that person an email.');

    }
}
