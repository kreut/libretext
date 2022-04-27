<?php

namespace App\Policies;

use App\Assignment;
use App\Course;
use App\Exceptions\Handler;
use App\Helpers\Helper;
use App\Score;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use \App\Traits\CommonPolicies;
use Illuminate\Support\Facades\Storage;

class AssignmentPolicy
{
    use HandlesAuthorization;
    use CommonPolicies;


    public function getClickerQuestion(User $user, Assignment $assignment)
    {

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the clicker questions for this assignment.');

    }

    public function linkAssignmentToLMS(User $user, Assignment $assignment)
    {
        return (int)$assignment->course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to link this assignment.');


    }

    public function order(User $user, Assignment $assignment, Course $course)
    {

        return (int)$course->user_id === $user->id
            ? Response::allow()
            : Response::deny('You are not allowed to re-order the assignments in that course.');

    }


    /**
     * Determine whether the user can view the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return Response
     */
    public function view(User $user, Assignment $assignment)
    {
        $has_access = $this->canView($user, $assignment);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to access this assignment.');
    }

    /**
     * @param User $user
     * @param Assignment $assignment
     * @return Response
     */
    public function getQuestionsWithCourseLevelUsageInfo(User $user, Assignment $assignment)

    {
        $has_access = (int)$assignment->course->user_id = $user->id || ($user->role === 2 && $assignment->course->public);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to access these assignment questions.');
    }

    function canView(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = (($assignment->course->public || Helper::isCommonsCourse($assignment->course)) && Helper::hasAnonymousUserSession())
                    || $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(3):
                $has_access = ($assignment->course->anonymous_users && Helper::isAnonymousUser())
                    || $assignment->course->enrollments->contains('user_id', $user->id);
                if (!$has_access){
                    try {
                        $contents = "User: $user->id Assignment: $assignment->id, Course: {$assignment->course->id}";
                        $date_time = Carbon::now('America/Los_Angeles');
                       // Storage::disk('s3')->put("logs/$date_time", $contents, ['StorageClass' => 'STANDARD_IA']);
                    } catch (Exception $e){
                        $h = new Handler(app());
                        $h->report($e);
                    }
                }
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        return $has_access;
    }

    /**
     * Determine whether the user can update the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return mixed
     */
    public function getQuestionsInfo(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to get questions for this assignment.');
    }

    /**
     * Determine whether the user can update the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return mixed
     */
    public function update(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to update this assignment.');
    }

    /**
     * Determine whether the user can update the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return mixed
     */
    public function createFromTemplate(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny("You are not allowed to create an assignment from this template.");
    }


    /**
     * Determine whether the user can update the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return mixed
     */
    public function showAssignment(User $user, Assignment $assignment)
    {
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to toggle whether students can view an assignment.');
    }

    public function releaseSolutions(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        if (!$has_access) {
            $message = 'You are not allowed to show/hide solutions.';
        }
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    public function showAssignmentStatistics(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }

        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to show/hide assignment statistics.');
    }


    public function scoresAccess(User $user, Assignment $assignment)
    {
        switch ($user->role) {
            case(2):
                $has_access = $assignment->course->user_id === $user->id;
                break;
            case(3):
                $has_access = $assignment->course->enrollments->contains('user_id', $user->id) && $assignment->students_can_view_assignment_statistics;
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
            default:
                $has_access = false;
        }
        return $has_access;
    }

    public function getAssignmentSummary(User $user, Assignment $assignment)
    {
        $has_access = $this->canView($user, $assignment);
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to retrieve this summary.');

    }

    public function scoresInfo(User $user, Assignment $assignment)
    {

        return $this->scoresAccess($user, $assignment)
            ? Response::allow()
            : Response::deny('You are not allowed to get these scores.');

    }



    public function gradersCanSeeStudentNames(User $user, Assignment $assignment)
    {
        return $this->ownsCourseByUser($assignment->course, $user)
            ? Response::allow()
            : Response::deny("You are not allowed to switch whether graders can view their students' names.");
    }
    public function showScores(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }

        if (!$has_access) {
            $message = 'You are not allowed to show/hide scores.';
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    public function showPointsPerQuestion(User $user, Assignment $assignment)
    {
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = $this->ownsCourseByUser($assignment->course, $user);
                break;
            case(4):
                $has_access = $assignment->course->isGrader();
                break;
        }
        if (!$has_access) {
            $message = 'You are not allowed to show/hide the points per question.';
        }

        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }


    /**
     * Determine whether the user can delete the assignment.
     *
     * @param User $user
     * @param Assignment $assignment
     * @return mixed
     */
    public function delete(User $user, Assignment $assignment)
    {
        //added (int) because wasn't working in the test
        return $user->id === (int)$assignment->course->user_id
            ? Response::allow()
            : Response::deny('You are not allowed to delete this assignment.');
    }


}
