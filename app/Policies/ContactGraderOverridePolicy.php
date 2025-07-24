<?php

namespace App\Policies;

use App\Assignment;
use App\ContactGraderOverride;
use App\Course;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ContactGraderOverridePolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param ContactGraderOverride $contactGraderOverride
     * @param Course $course
     * @param $contact_override_grader_id
     * @return Response
     */
    public function update(User                  $user,
                           ContactGraderOverride $contactGraderOverride,
                           Course                $course,
                                                 $contact_override_grader_id): Response
    {
        $has_access = false;
        if (!$course->ownsCourseOrIsCoInstructor($user->id)) {
            $message = "You are not allowed to update the grader contact information for that course.";
        } else {
            $co_instructors = DB::table('co_instructors')->where('course_id', $course->id)->get();
            $co_instructor_user_ids = $co_instructors->isNotEmpty()
                ? $co_instructors->pluck('user_id')->toArray()
                : [];
            $has_access = $contact_override_grader_id === -1
                || !$contact_override_grader_id
                || $contact_override_grader_id === $user->id
                || in_array($contact_override_grader_id, $course->graders()->pluck('id')->toArray())
                    || in_array($contact_override_grader_id, $co_instructor_user_ids);
            if (!$has_access) {
                $message = 'You are not allowed to update the grader contact information to that user.';
            }
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param ContactGraderOverride $contactGraderOverride
     * @param Course $course
     * @return Response
     */
    public function show(User $user, ContactGraderOverride $contactGraderOverride, Course $course): Response
    {

        return $course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to email one of the graders for this course.');
    }
}
