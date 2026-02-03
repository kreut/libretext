<?php

namespace App\Policies;

use App\Assignment;
use App\ForgeAssignmentQuestion;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;

class ForgeAssignmentQuestionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param ForgeAssignmentQuestion $forgeAssignmentQuestion
     * @param Assignment $assignment
     * @param string $central_identity_id
     * @return Response
     */
    public function getSubmissionByAssignmentQuestionStudent(User                    $user,
                                                             ForgeAssignmentQuestion $forgeAssignmentQuestion,
                                                             Assignment              $assignment,
                                                             string                  $central_identity_id): Response
    {
        $has_access = true;
        $message = '';
        $course = $assignment->course;
        if (!$course->isGrader() && !$course->ownsCourseOrIsCoInstructor($user->id)) {
            $has_access = false;
            $message = "You are not a grader and do not own that course so cannot get submissions by assignment-question-student.";
        }
        $student = User::where('central_identity_id', $central_identity_id)->first();
        $enrolled_in_course = DB::table('enrollments')
            ->where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->exists();
        if (!$enrolled_in_course) {
            $has_access = false;
            $message = "That student is not enrolled in your course.";
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }
}
