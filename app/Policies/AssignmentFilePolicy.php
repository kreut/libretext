<?php

namespace App\Policies;

use App\User;
use App\Assignment;
use App\AssignmentFile;
use App\Course;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class AssignmentFilePolicy
{
    use HandlesAuthorization;

    public function viewAssignmentFilesByAssignment(User $user, AssignmentFile $assignmentFile, Course $course){

         return ((int) $course->user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to access these assignment files.');
}
    public function uploadAssignmentFile(User $user, AssignmentFile $assignmentFile, Assignment $assignment)
    {

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to access this assignment.');

    }
    public function canProvideFeedback($assignment, $student_user_id, $instructor_user_id){
        //student is enrolled in the course containing the assignment
        //the person doing the upload is the owner of the course
        return $assignment->course->enrollments->contains('user_id',  $student_user_id) && ($assignment->course->user_id === $instructor_user_id);
    }

    public function storeTextFeedback(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to submit comments for this assignment.');

    }

    public function getAssignmentFileInfoByStudent(User $user, AssignmentFile $assignmentFile, int $assignment_id){
        $user_is_owner_of_assignment_file = $assignmentFile
                                                ->where('user_id', $user->id)
                                                ->where('assignment_id', $assignment_id);

        return $user_is_owner_of_assignment_file
            ? Response::allow()
            : Response::deny('You are not allowed to get the information on this file submission.');

    }

    public function uploadFileFeedback(User $user, AssignmentFile $assignmentFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload feedback for this assignment.');

    }
}
