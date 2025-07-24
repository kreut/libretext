<?php

namespace App\Policies;

use App\Assignment;
use App\ReportToggle;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ReportTogglePolicy
{
    use HandlesAuthorization;
    public function update(User $user, ReportToggle $reportToggle, Assignment $assignment): Response
    {
        return $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny();
    }
}
