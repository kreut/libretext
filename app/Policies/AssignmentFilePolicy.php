<?php

namespace App\Policies;

use App\User;
use App\Assignment;
use App\AssignmentFile;
use App\SubmissionFile;
use App\Course;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class AssignmentFilePolicy
{
    use HandlesAuthorization;

    public function downloadAssignmentFile(User $user, AssignmentFile $assignmentFile, SubmissionFile $submissionFile, int $assignment_id, string $filename)
    {
        switch ($user->role) {
            case(2):
                $has_access = (int)Assignment::find($assignment_id)->course->user_id === $user->id;
                break;
            case(3):
                //student who owns the assignment or the file feedback
                $user_id = $submissionFile->where('assignment_id', $assignment_id)
                    ->where('submission', $filename)
                    ->orWhere('file_feedback', $filename)
                    ->value('user_id');
                $has_access = (int)$user_id === $user->id;
                break;
            case(4):
                $has_access = (int)Assignment::find($assignment_id)->course->isGrader();
                break;
        }

        return ($has_access) ?
            Response::allow()
            : Response::deny('You are not allowed to download that assignment file.');

    }

    public function createTemporaryUrl(User $user, AssignmentFile $assignmentFile, Course $course)
    {

        return ($course->isGrader() || (int)$course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to create a temporary URL.');
    }

    public function viewAssignmentFilesByAssignment(User $user, AssignmentFile $assignmentFile, Assignment $assignment)
    {
        $message = '';
        if (((int)$assignment->course->user_id !== $user->id)) {
            $message = 'You are not allowed to access these assignment files.';
        }

        return (((int)$assignment->course->user_id === $user->id) && ((int)$assignment->assignment_files === 1))
            ? Response::allow()
            : Response::deny($message);
    }

    public function uploadAssignmentFile(User $user, AssignmentFile $assignmentFile, Assignment $assignment)
    {

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload a file to this assignment.');

    }

    public function canProvideFeedback($user, $assignment, $student_user_id, $instructor_user_id)
    {
        //student is enrolled in the course containing the assignment
        //the person doing the upload is the owner of the course

        $has_grader_access = false;
        if ($user->role === 4) {
            $accessible_assignment_ids = $assignment->course->accessbileAssignmentsByGrader($user->id);

            $has_grader_access = $assignment->course->isGrader()
                && $accessible_assignment_ids
                && $accessible_assignment_ids[$assignment->id];
        }

        return $assignment->course->enrollments->contains('user_id', $student_user_id)
            && ($has_grader_access || ((int)$assignment->course->user_id === $instructor_user_id));
    }

    public function storeTextFeedback(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($user, $assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to submit comments for this assignment.');

    }

    public function getAssignmentFileInfoByStudent(User $user, AssignmentFile $assignmentFile, SubmissionFile $submissionFile, int $assignment_id)
    {
        $assignment_file_user_id = $submissionFile
            ->where('user_id', $user->id)
            ->where('assignment_id', $assignment_id)
            ->value('user_id');


        return ((int)$assignment_file_user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the information on this file submission.');

    }

    public function uploadFileFeedback(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($user, $assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload feedback for this assignment.');

    }
    public function storeScore(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($user, $assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to provide a score for this assignment.');

    }

    public function viewGrading(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($user, $assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to grade this assignment.');

    }

    public function uploadAudioFeedback(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($user, $assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to provide feedback for this file.');

    }
}
