<?php

namespace App\Policies;

use App\Assignment;
use App\RubricCategorySubmission;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Traits\CommonPolicies;
use Illuminate\Auth\Access\Response;
use App\Traits\GeneralSubmissionPolicy;

class RubricCategorySubmissionPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;
    use GeneralSubmissionPolicy;

    /**
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param User $user
     * @param Assignment $assignment
     * @param User $student_user
     * @return Response
     */
    public function getByAssignmentQuestionAndUser(User                     $user,
                                           RubricCategorySubmission $rubricCategorySubmission,
                                           Assignment               $assignment,
                                           User                     $student_user): Response
    {
        return $user->id === $student_user->id || $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment->id, $student_user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get these rubric category submissions.');

    }

    /**
     * @param User $user
     * @param RubricCategorySubmission $rubricCategorySubmission
     * @param $assignment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function store(User $user, RubricCategorySubmission $rubricCategorySubmission, $assignment, int $assignment_id, int $question_id): Response
    {
        $response = $this->canSubmitBasedOnGeneralSubmissionPolicy($user, $assignment, $assignment_id, $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny($response['message']);
    }
}


