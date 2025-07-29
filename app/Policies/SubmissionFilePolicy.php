<?php

namespace App\Policies;

use App\Grader;
use App\Section;
use App\User;
use App\Assignment;
use App\SubmissionFile;
use App\Course;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\Response;

class SubmissionFilePolicy
{
    use HandlesAuthorization;

    public function getFilesFromS3(User $user, SubmissionFile $submissionFile, Assignment $assignment, User $studentUser, Grader $grader)
    {
        $is_student_in_course = $assignment->course->enrollments->contains('user_id', $studentUser->id);

        switch ($user->role) {
            case(2):
                $has_access = $is_student_in_course && $assignment->course->ownsCourseOrIsCoInstructor($user->id);
                break;
            case(3):
                $has_access = $user->id === $studentUser->id;
                break;
            case(4):
                $section_id = $assignment->course->enrollments->where('user_id', $studentUser->id)->pluck('section_id')->first();
                $grader_sections = $grader->where('user_id', $user->id)->select('section_id')->get();

                $override_access = false;
                $access_level_override = $assignment->graders()
                    ->where('assignment_grader_access.user_id', $user->id)
                    ->first();
                if ($access_level_override) {
                    $override_access = $access_level_override->pivot->access_level;
                }

                $has_access = $is_student_in_course && ($override_access || $grader_sections->isNotEmpty() && in_array($section_id, $grader_sections->pluck('section_id')->toArray()));
                break;
            default:
                $has_access = false;

        }
        return $has_access
            ? Response::allow()
            : Response::deny('You are not allowed to view that submission file.');


    }

    public function downloadAssignmentFile(User $user, AssignmentFile $submissionFile, int $assignment_id, string $submission)
    {


        if ($user->role === 3) {
            //student who owns the assignment
            $user_id = $submissionFile->where('assignment_id', $assignment_id)
                ->where('submission', $submission)
                ->value('user_id');

        } else {
            //instructor is owner of the course
            $user_id = Assignment::find($assignment_id) ? Assignment::find($assignment_id)->course->user_id : null;
        }


        return ((int)$user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to download that assignment file.');

    }

    public function getUngradedSubmissions(User $user, SubmissionFile $submissionFile, Course $course){
        return ($course->ownsCourseOrIsCoInstructor($user->id))
            ? Response::allow()
            : Response::deny('You are not allowed to get the ungraded submissions for this course.');

    }
    public function createTemporaryUrl(User $user, SubmissionFile $submissionFile, Course $course)
    {

        return ($course->ownsCourseOrIsCoInstructor($user->id))
            ? Response::allow()
            : Response::deny('You are not allowed to create a temporary URL.');
    }

    public function viewAssignmentFilesByAssignment(User $user, SubmissionFile $submissionFile, Assignment $assignment, int $sectionId)
    {
        $message = '';
        $has_access = false;
        switch ($user->role) {
            case(2):
                $has_access = (int)$assignment->course->ownsCourseOrIsCoInstructor($user->id);
                break;
            case(4):
                if ($sectionId) {
                    $override_access = false;
                    $access_level_override = $assignment->graders()
                        ->where('assignment_grader_access.user_id', $user->id)
                        ->first();
                    if ($access_level_override) {
                        $override_access = $access_level_override->pivot->access_level;
                    }
                    $has_access = Section::find($sectionId)->isGrader() || $override_access;
                } else {
                    $has_access = $assignment->course->isGrader();
                }
        }

        if (!$has_access) {
            $message = 'You are not allowed to access these submissions for grading.';
        }

        return ($has_access)
            ? Response::allow()
            : Response::deny($message);
    }

    public function uploadSubmissionFile(User $user, SubmissionFile $submissionFile, Assignment $assignment)
    {

        return $assignment->course->enrollments->contains('user_id', $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload a file to this assignment.');

    }

    public function canProvideFeedback($assignment, $student_user_id, $instructor_user_id)
    {
        //student is enrolled in the course containing the assignment
        //the person doing the upload is the owner of the course or a grader

        return $assignment->course->enrollments->contains('user_id', $student_user_id) && ((int)$assignment->course->user_id === $instructor_user_id);
    }

    public function storeGrading(User $user, SubmissionFile $submissionFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to store the score or provide feedback for this student.');

    }


    public function getAssignmentFileInfoByStudent(User $user, SubmissionFile $submissionFile, int $assignment_id)
    {
        $assignment_file_user_id = $assignmentFile
            ->where('user_id', $user->id)
            ->where('assignment_id', $assignment_id)
            ->value('user_id');


        return ((int)$assignment_file_user_id === $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to get the information on this file submission.');

    }

    public function uploadFileFeedback(User $user, SubmissionFile $submissionFile, User $student_user, Assignment $assignment)
    {

        return $this->canProvideFeedback($assignment, $student_user->id, $user->id)
            ? Response::allow()
            : Response::deny('You are not allowed to upload feedback for this assignment.');

    }
}
