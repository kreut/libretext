<?php

namespace App\Policies;

use App\Assignment;
use App\LearningTreeTimeLeft;
use App\Traits\GeneralSubmissionPolicy;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class LearningTreeTimeLeftPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;

    public function update(User $user, LearningTreeTimeLeft $learningTreeTimeLeft, $assignment, int $assignment_id, int $question_id)
    {
        $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny($response['message']);
    }


    public function getTimeLeft(User $user, LearningTreeTimeLeft $learningTreeTimeLeft, int $assignment_id, int $root_node_question_id)
    {
        $has_access = true;
        $message = 'No get time left message provided';
        $assignment = Assignment::find($assignment_id);
        $is_student_in_course = $assignment->course->enrollments->contains('user_id', $user->id);
        $question_in_assignment = in_array($root_node_question_id, $assignment->questions->pluck('id')->toArray());
        if (!$is_student_in_course) {
            $has_access = false;
            $message = "You are not a student in this course.";
        }
        if ($has_access && !$question_in_assignment) {
            $has_access = false;
            $message = "That is not a question in the assignment.";

        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }
}
