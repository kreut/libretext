<?php

namespace App\Policies;

use App\Assignment;
use App\Course;
use App\MathPix;
use App\NonUpdatedQuestionRevision;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MathPixPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param MathPix $mathPix
     * @param int $user_id
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function convertToSmiles(User $user, MathPix $mathPix, int $user_id, int $assignment_id, int $question_id): Response
    {
        $assignment = Assignment::find($assignment_id);
        $assignment_question_exists = in_array($question_id, $assignment->questions->pluck('id')->toArray());

        $has_access = $assignment->course->enrollments->contains('user_id', $user_id) && $assignment_question_exists;
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to convert this to SMILES.');
    }

    /**
     * @param User $user
     * @param MathPix $mathPix
     * @param int $user_id
     * @param int $assignment_id
     * @param int $question_id
     * @return Response
     */
    public function temporaryUrl(User $user, MathPix $mathPix, int $user_id, int $assignment_id, int $question_id): Response
    {
        $assignment = Assignment::find($assignment_id);
        $assignment_question_exists = in_array($question_id, $assignment->questions->pluck('id')->toArray());
        $has_access = $assignment->course->ownsCourseOrIsCoInstructor($user->id)
            && ($assignment->course->enrollments->contains('user_id', $user_id) || $assignment->course->isGrader())
            && $assignment_question_exists;
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to get a temporary URL for this SMILE.');
    }
}
