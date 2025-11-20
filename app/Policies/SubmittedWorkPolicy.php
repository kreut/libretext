<?php

namespace App\Policies;

use App\Assignment;
use App\Grader;
use App\SubmittedWork;
use App\Traits\GeneralSubmissionPolicy;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\DB;
use Spipu\Html2Pdf\Tag\Html\Sub;

class SubmittedWorkPolicy
{
    use HandlesAuthorization;
    use GeneralSubmissionPolicy;

    /**
     * @param User $user
     * @param SubmittedWork $submittedWork
     * @param Assignment $assignment
     * @return Response
     */
    public function delete(User          $user,
                           SubmittedWork $submittedWork,
                           Assignment    $assignment): Response
    {
        $has_access_info = $this->canSubmitWork($user, $assignment);
        $has_access = $has_access_info['has_access'];
        $message = $has_access_info['message'];
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param $submittedWork
     * @param $assignment
     * @return Response
     */
    public function store(User $user, $submittedWork, $assignment): Response
    {
        $has_access_info = $this->canSubmitWork($user, $assignment);
        $has_access = $has_access_info['has_access'];
        $message = $has_access_info['message'];
        return $has_access
            ? Response::allow()
            : Response::deny($message);

    }

    /**
     * @param User $user
     * @param $submittedWork
     * @param $assignment
     * @return Response
     */
    public function storeAudioSubmittedWork(User $user, $submittedWork, $assignment): Response
    {
        $has_access_info = $this->canSubmitWork($user, $assignment);
        $has_access = $has_access_info['has_access'];
        $message = $has_access_info['message'];
        return $has_access
            ? Response::allow()
            : Response::deny($message);
    }

    /**
     * @param User $user
     * @param $submittedWork
     * @param Assignment $assignment
     * @param User $studentUser
     * @param Grader $grader
     * @return Response
     */
    public function getSubmittedWorkWithPendingScore(User       $user,
                                                                $submittedWork,
                                                     Assignment $assignment,
                                                     User       $studentUser,
                                                     Grader     $grader): Response
    {
        return $this->canViewSubmittedFiles($user, $assignment, $studentUser, $grader)
            ? Response::allow()
            : Response::deny("You are not allowed to get the submitted work for this question.");
    }

    /**
     * @param User $user
     * @param Assignment $assignment
     * @return array
     */
    public function canSubmitWork(User $user, Assignment $assignment): array
    {
        $past_due = time() > strtotime($assignment->assignToTimingByUser('due'));
        $enrolled_in_course = DB::table('enrollments')
            ->where('course_id', $assignment->course->id)
            ->where('user_id', $user->id)
            ->exists();
        $has_access = true;
        $message = '';
        if (!$enrolled_in_course) {
            $message = "You cannot submit work since you are not enrolled in this course.";
            $has_access = false;
        } else if (!$user->fake_student && $past_due) {
            $message = "You cannot submit work since this assignment is past due.";
            $has_access = false;
        }
        return ['has_access' => $has_access, 'message' => $message];
    }
}
