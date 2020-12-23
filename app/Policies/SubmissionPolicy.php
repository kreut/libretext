<?php

namespace App\Policies;

use App\User;
use App\Submission;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;
use App\Traits\LatePolicy;

class SubmissionPolicy
{
    use HandlesAuthorization;
    use LatePolicy;

    /**
     * @param User $user
     * @param Submission $submission
     * @param $assignment
     * @param int $assignment_id
     * @param int $question_id
     * @return Response]
     */
    public function store(User $user, $submission, $assignment, int $assignment_id, int $question_id)
    {
        $response = $this->canSubmitBasedOnLatePolicy( $user,  $assignment, $assignment_id,  $question_id);
        $has_access = $response['type'] === 'success';
        return $has_access
            ? Response::allow()
            : Response::deny($response['message']);
    }
}
