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
            $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
            $has_access = $response['type'] === 'success';
            return $has_access
                ? Response::allow()
                : Response::deny($response['message']);
        }
    }
}
