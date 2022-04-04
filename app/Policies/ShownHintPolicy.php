<?php

namespace App\Policies;

use App\ShownHint;
use App\Traits\GeneralSubmissionPolicy;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ShownHintPolicy
{
    use GeneralSubmissionPolicy;
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param ShownHint $shownHint
     * @param $assignment
     * @param int $question_id
     * @return Response
     */
    public function store(User $user, ShownHint $shownHint, $assignment, int $question_id)
    {
        $assign_to_timing = $assignment->assignToTimingByUser();
        $has_access = true;
        $message = '';
        if (!$assignment->can_view_hint) {
            $message = "The instructor does not want students to view this hint.";
            $has_access = false;
        }
        if (!$assign_to_timing) {
            $message = "You cannot view the hint since you were not assigned to this assignment.";
            $has_access = false;
        } else {
            if ($assign_to_timing->available_from > time()) {
                $message = 'You cannot view the hint since the assignment is not available.';
                $has_access = false;
            }
        }
        if (!$assignment->questions->contains($question_id)) {
            $message = 'You cannot view the hint since the question is not in your assignment.';
            $has_access = false;
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            $message = 'You cannot view the hint since you are not part of the course.';
            $has_access = false;
        }
        if (session()->get('instructor_user_id')) {
            //logged in as student
            $has_access = true;

        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

}
