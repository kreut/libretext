<?php

namespace App\Policies;

use App\Assignment;
use App\LearningTreeNode;
use App\Traits\GeneralSubmissionPolicy;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;


class LearningTreeNodePolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;

    public function resetRootNodeSubmission(user             $user,
                                            LearningTreeNode $learningTreeNode,
                                            Assignment       $assignment,
                                            int              $assignment_id,
                                            int              $question_id): Response
    {
        {
            $has_access = true;
            $message = '';

            if (!in_array($question_id, $assignment->questions->pluck('id')->toArray())){
                $has_access = false;
                $message = "That question cannot be reset since it's not in the assignment.";
            }
            if ($has_access && !$assignment->course->enrollments->contains('user_id', $user->id)){
                $has_access = false;
                $message = "You are not a student in this course so you cannot reset the root node submission.";
            }

            return $has_access
                ? Response::allow()
                : Response::deny($message);
        }
    }
}
