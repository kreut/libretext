<?php

namespace App\Policies;

use App\Score;
use App\User;
use App\Assignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ScorePolicy
{
    use HandlesAuthorization;
    use \App\Traits\CommonPolicies;

    /**
     * Determine whether the user can update the score.
     *
     * @param  \App\User  $user
     * @param  \App\Score  $score
     * @return mixed
     */
    public function update(User $user,  Score $score, int $assignment_id, int $student_user_id)
    {

        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to update this score.');

    }

    public function getScoreByAssignmentAndStudent(User $user,  Score $score, int $assignment_id, int $student_user_id)
    {

        return $this->ownsResourceByAssignmentAndStudentOrWasGivenAccessByOwner($user, $assignment_id, $student_user_id)
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve this score.');

    }


}
