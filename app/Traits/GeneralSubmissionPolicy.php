<?php


namespace App\Traits;

use App\User;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;


trait GeneralSubmissionPolicy


{
    /**
     * @param User $user
     * @param $assignment
     * @param int $assignment_id
     * @param int $question_id
     * @param string $level
     * @return array
     */
    public function canSubmitBasedOnGeneralSubmissionPolicy(User $user, $assignment, int $assignment_id, int $question_id, $level = 'question'): array
    {
        $response['type'] = 'error';
        $response['message'] = '';

        /** $db_question_updated_at = Question::find($question_id)->updated_at->timestamp;
         * if ((int) $db_question_updated_at !==  (int) (request()->cookie('loaded_question_updated_at'))) {
         * $response['message'] = 'It looks like this question has been updated!  Please refresh the page and re-submit.';
         * return $response;
         * }**/
        if ($assignment->course->anonymous_users && (Helper::isAnonymousUser() || Helper::hasAnonymousUserSession())) {
            $response['type'] = 'success';
            return $response;
        }

        $assign_to_timing = $assignment->assignToTimingByUser();
        if (!$assign_to_timing) {
            $response['message'] = "No responses will be saved since you were not assigned to this assignment.";
            return $response;
        }
        $available_from = $assign_to_timing->available_from;
        $due = $assign_to_timing->due;

        if ($assignment->assessment_type === 'real time' && $assignment->number_of_allowed_attempts === 'unlimited') {
            $submission = DB::table('submissions')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->where('user_id', $user->id)
                ->first();
            if ($submission && $submission->show_solution) {
                $response['message'] = "The solution is already available so you can't resubmit.";
                return $response;
            }
        }
        if ($assignment->assessment_type === 'clicker') {
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment_id)
                ->where('question_id', $question_id)
                ->select()
                ->first(['clicker_start', 'clicker_end']);
            if (!$assignment_question->clicker_start || (!(time() >= strtotime($assignment_question->clicker_start) && time() <= strtotime($assignment_question->clicker_end)))) {
                $response['message'] = "This question is currently not open for submission.";
                return $response;
            }

        }
        if ($level === 'question' && !$assignment->questions->contains($question_id)) {
            $response['message'] = 'No responses will be saved since that question is not in the assignment.';
            return $response;
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            $response['message'] = 'No responses will be saved since the assignment is `not` part of your course.';
            return $response;
        }
        if (session()->get('instructor_user_id')) {
            //logged in as student
            $response['type'] = 'success';
            return $response;
        }

        if (strtotime($available_from) > time()) {
            $response['message'] = 'No responses will be saved since this assignment is not yet available.';
            return $response;
        }

        $file_submission = DB::table('submission_files')
            ->where('assignment_id', $assignment_id)
            ->where('question_id', $question_id)
            ->where('user_id', $user->id)
            ->select('date_graded')
            ->first();

        if ($file_submission && $file_submission->date_graded && $assignment->scoring_type === 'p') {
            $response['message'] = 'Your submission has already been graded and may not be re-submitted.';
            return $response;
        }

        //first let's see if there's an extension
        $extension = DB::table('extensions')
            ->select('extension')
            ->where('assignment_id', $assignment_id)
            ->where('user_id', $user->id)
            ->first('extension');
        $past_due = time() > strtotime($due);
        //check to see if the instructor accidentally released scores (which will have comments) or released solutions
        switch ($past_due) {
            case(false):
                if ($assignment->assessment_type === 'delayed') {
                    if ($assignment->show_scores) {
                        $response['message'] = 'No responses will be saved since the scores to this assignment have been released.';
                        return $response;
                    }
                }
                if (in_array($assignment->assessment_type, ['delayed', 'learning tree'])) {
                    if ($assignment->solutions_released) {
                        $response['message'] = 'No responses will be saved since the solutions to this assignment have been released.';
                        return $response;
                    }
                }
                break;
            case(true):
                if ($extension) {
                    if (strtotime($extension->extension) < time()) {
                        $response['message'] = 'No responses will be saved since your extension for this assignment has passed.';
                    } else {
                        $response['type'] = 'success';
                    }
                    return $response;
                }

                if ($assignment->late_policy === 'not accepted') {
                    $response['message'] = 'No responses will be saved since the due date for this assignment has passed.';
                    return $response;
                }
                if (in_array($assignment->late_policy, ['deduction', 'marked late'])) {
                    //now let's check the late policy deadline
                    //if past policy deadline
                    if (strtotime($assign_to_timing->final_submission_deadline) < time()) {
                        $response['message'] = 'No more late responses are being accepted.';
                        return $response;
                    }
                }
                break;
        }
        $response['type'] = 'success';
        return $response;
    }


}
