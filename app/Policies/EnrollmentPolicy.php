<?php

namespace App\Policies;


use App\User;
use App\Enrollment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EnrollmentPolicy
{
    use HandlesAuthorization;

    public function view(User $user)
    {
        return ($user->role === 3)
            ? Response::allow()
            : Response::deny('You must be a student to view your enrollments.');

    }

    public function store(User $user)
    {
        return ($user->role === 3)
            ? Response::allow()
            : Response::deny('You must be a student to enroll in a course.');

    }
}
