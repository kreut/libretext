<?php

namespace App\Policies;

use App\Assignment;
use App\PreSignedURL;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class PreSignedURLPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @return Response
     */
    public function questionMediaPreSignedURL(User $user): Response
    {
        return in_array($user->role,[2,5])
            ? Response::allow()
            : Response::deny('You are not allowed to upload question media.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function qtiPreSignedURL(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to upload QTI files.');

    }

    /**
     * @param User $user
     * @param PreSignedURL $preSignedURL
     * @param Assignment $assignment
     * @param string $upload_file_type
     * @return Response
     */
    public function preSignedURL(User         $user,
                                 PreSignedURL $preSignedURL,
                                 Assignment   $assignment,
                                 string       $upload_file_type): Response
    {

        $has_access = false;
        switch ($upload_file_type) {
            case('solution'):
                $has_access = $user->role === 2;
                break;
            case('submission'):
                $has_access = $user->role === 3 && $assignment->course->enrollments->contains('user_id', $user->id);
        }


        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to upload $upload_file_type files.");

    }
}
