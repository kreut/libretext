<?php

namespace App\Policies;

use App\Assignment;
use App\MyFavorite;
use App\RubricPointsBreakdown;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class RubricPointsBreakdownPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param RubricPointsBreakdown $rubricPointsBreakdown
     * @param Assignment $assignment
     * @param User $student_user
     * @return Response
     */
    public function getByAssignmentQuestionUser(User                  $user,
                                                RubricPointsBreakdown $rubricPointsBreakdown,
                                                Assignment            $assignment,
                                                User                  $student_user): Response
    {
        $enrolled_in_course = $assignment->course->enrollments->contains('user_id', $student_user->id);
        return (int)$assignment->course->user_id === (int)$user->id && $enrolled_in_course
            ? Response::allow()
            : Response::deny("You are not allowed to get the rubric points breakdown for that assignment-question-user.");

    }

    /**
     * @param User $user
     * @param RubricPointsBreakdown $rubricPointsBreakdown
     * @param Assignment $assignment
     * @return Response
     */
    public function existsByAssignmentQuestion(User                  $user,
                                               RubricPointsBreakdown $rubricPointsBreakdown,
                                               Assignment            $assignment): Response
    {

        return (int)$assignment->course->user_id === (int)$user->id
            ? Response::allow()
            : Response::deny("You are not allowed to check if a rubric points breakdown exists for that assignment.");

    }
}
