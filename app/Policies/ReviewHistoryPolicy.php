<?php

namespace App\Policies;

use App\Assignment;
use App\ReviewHistory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ReviewHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param ReviewHistory $reviewHistory
     * @param Assignment $assignment
     * @return Response
     */
    public function update(User $user, ReviewHistory $reviewHistory, Assignment $assignment): Response
    {
        $past_due = $assignment->assignToTimingByUser('due') && time() > strtotime($assignment->assignToTimingByUser('due'));
        return $past_due && !$user->fake_student
            ? Response::allow()
            : Response::deny();
    }
}
