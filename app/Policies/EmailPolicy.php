<?php

namespace App\Policies;

use App\Assignment;
use App\User;
use App\Email;
use App\SubmissionFile;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class EmailPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param Email $email
     * @param int $to_user_id
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function contactGrader(User $user, Email $email, int $to_user_id, int $assignment_id, int $question_id): Response
    {
        $has_access = false;
        //student can send to grader
        if ($user->role === 3) {
            $course = Assignment::find($assignment_id)->course;
            $enrolled = $course->enrollments->contains('user_id', $user->id);
            $to_is_grader = false;
            $grader_section = DB::table('graders')->where('user_id', $to_user_id)->first();
            if ($grader_section) {
                $to_is_grader = DB::table('sections')
                    ->where('id', $grader_section->section_id)
                    ->where('course_id', $course->id)
                    ->first();
            }
//get the courses for which they're enrolled and let them send the email if it's a grader or instructor in the course
            $has_access = $enrolled && ($to_user_id === $course->user_id || $to_is_grader);
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to send that person an email.');

    }
}
