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
        $has_access = false;

        switch ($user->role) {
            case(2):
                $has_access = (int)$assignment->course->user_id === (int)$user->id;
                break;
            case(3):
                $has_access = $user->id === $student_user->id && $enrolled_in_course;
                break;
            case(4):
                $has_access = $assignment->course->isGrader() && $enrolled_in_course;
                break;
        }


        return $has_access
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

        $has_access = false;
        if ($user->role === 4) {
            $has_access = $assignment->course->isGrader();
        }
        if ($user->role === 2) {
            $has_access = (int)$assignment->course->user_id === (int)$user->id;
        }

        return $has_access
            ? Response::allow()
            : Response::deny("You are not allowed to check if a rubric points breakdown exists for that assignment.");

    }
}
