<?php

namespace App\Policies;

use App\Assignment;
use App\PreSignedURL;
use App\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class PreSignedURLPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return Response
     */
    public function studentRoster(User $user): Response
    {
        return $user->role === 2
            ? Response::allow()
            : Response::deny('You are not allowed to upload a student roster.');

    }

    /**
     * @param User $user
     * @param PreSignedURL $preSignedURL
     * @param Assignment $assignment
     * @param int $question_id
     * @return Response
     */
    public function submittedWork(User $user, PreSignedURL $preSignedURL, Assignment $assignment, int $question_id): Response
    {
        $authorized = in_array($question_id, $assignment->questions->pluck('id')->toArray())
            && $assignment->course->enrollments->contains('user_id', $user->id);
        return $authorized
            ? Response::allow()
            : Response::deny('You are not allowed to submit work for this question.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function questionMediaPreSignedURL(User $user): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to upload question media.');

    }

    /**
     * @param User $user
     * @return Response
     */
    public function discussItSignedURL(User $user): Response
    {
        return in_array($user->role, [2, 5])
            ? Response::allow()
            : Response::deny('You are not allowed to upload DiscussIt media.');

    }

    /**
     * @param User $user
     * @param PreSignedURL $preSignedURL
     * @param Assignment $assignment
     * @return Response
     */
    public function discussItComments(User $user, PreSignedURL $preSignedURL, Assignment $assignment)
    {
        $has_access = ($user->role === 3 && $assignment->course->enrollments->contains('user_id', $user->id))
            || ($user->role === 2 && $assignment->course->ownsCourseOrIsCoInstructor($user->id));

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to submit this comment');

    }

    public function vttPreSignedURL(User $user, PreSignedURL $preSignedURL, string $s3_key): Response
    {
        $question_media_owner = DB::table('question_media_uploads')
            ->join('questions', 'question_media_uploads.question_id', '=', 'questions.id')
            ->where('s3_key', $s3_key)
            ->first();
        return $question_media_owner
            ? Response::allow()
            : Response::deny('You do not own that media so you cannot upload a transcript.');

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
     * @return Response
     */
    public function structure(User         $user,
                              PreSignedURL $preSignedURL,
                              Assignment   $assignment): Response
    {
        $has_access = $assignment->course->enrollments->contains('user_id', $user->id);
        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to upload a chemical structure for this question.");

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
                break;
            case('structure'):
                $has_access = $assignment->course->ownsCourseOrIsCoInstructor($user->id)
                    || ($user->role === 3 && $assignment->course->enrollments->contains('user_id', $user->id));
        }


        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to upload $upload_file_type files.");

    }
}
