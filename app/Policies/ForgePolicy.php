<?php

namespace App\Policies;

use App\Assignment;
use App\Course;
use App\Enrollment;
use App\Forge;
use App\ForgeAssignmentQuestion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ForgePolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @param Forge $forge
     * @param Assignment $assignment
     * @return Response
     */
    public function getAssignTosByAssignmentQuestionLoggedInUser(User $user, Forge $forge, Assignment $assignment): Response
    {
        $enrolled_user_ids = Enrollment::where('course_id', $assignment->course->id)
            ->get('user_id')
            ->pluck('user_id')
            ->toArray();
        $has_access = in_array($user->id, $enrolled_user_ids);
        return $has_access
            ? Response::allow()
            : Response::deny("You cannot get the assign tos by assignment-question-logged-in-user.");
    }

    /**
     * @param User $user
     * @param Forge $forge
     * @param Course $course
     * @param int $student_user_id
     * @return Response
     */
    public function getAssignTosByAssignmentQuestionUser(User   $user,
                                                         Forge  $forge,
                                                         Course $course,
                                                         int    $student_user_id): Response
    {

        $has_access = true;
        $message = '';
        $owns_course = $course->user_id === $user->id;
        if (!$owns_course) {
            $has_access = false;
            $message = "You do not own that course so cannot get the draft assign tos.";
        }
        if ($has_access) {
            $enrolled_user_ids = $course->enrolledUsers->pluck('id')->toArray();
            if (!in_array($student_user_id, $enrolled_user_ids)) {
                $has_access = false;
                $message = "The student with $user->central_identity_id is not enrolled in this course so you cannot get the draft assign tos.";
            }
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param Forge $forge
     * @param Course $course
     * @return Response
     */
    public function initialize(User $user, Forge $forge, Course $course): Response
    {
        $has_access = true;
        switch ($user->role) {
            case(2):
                $has_access = $user->id === $course->user_id;
                if (!$has_access) {
                    $message = "You cannot initialize the Forge settings since you do not own this course.";
                }
                break;
            case(3);
                $enrolled_user_ids = Enrollment::where('course_id', $course->id)
                    ->get('user_id')
                    ->pluck('user_id')
                    ->toArray();
                if (!in_array($user->id, $enrolled_user_ids)) {
                    $has_access = false;
                    $message = "You are not enrolled in this course so you cannot initialize the Forge settings.";
                }
                break;
            default:
                $has_access = false;
                $message = "You cannot initialize the Forge settings.";
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }


}
