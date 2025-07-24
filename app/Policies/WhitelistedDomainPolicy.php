<?php

namespace App\Policies;

use App\Course;
use App\User;
use App\WhitelistedDomain;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WhitelistedDomainPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param WhitelistedDomain $whitelistedDomain
     * @param Course $course
     * @return Response
     */
    public function store(User $user, WhitelistedDomain $whitelistedDomain, Course $course): Response
    {
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to store a whitelisted domain to that course.');

    }

    /**
     * @param User $user
     * @param WhitelistedDomain $whitelistedDomain
     * @param Course $course
     * @return Response
     */
    public function destroy(User $user, WhitelistedDomain $whitelistedDomain): Response
    {
        $course = Course::find($whitelistedDomain->course_id);
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to delete a whitelisted domain from that course.');

    }

    /**
     * @param User $user
     * @param WhitelistedDomain $whitelistedDomain
     * @param Course $course
     * @return Response
     */
    public function getByCourse(User $user, WhitelistedDomain $whitelistedDomain, Course $course): Response
    {
        return $course->ownsCourseOrIsCoInstructor($user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the whitelisted domains for that course.');


    }
}
