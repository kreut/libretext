<?php


namespace App\Traits;
use App\User;
use Illuminate\Support\Facades\DB;

trait GeneralSubmissionPolicy
{
    public function canSubmitBasedOnGeneralSubmissionPolicy(User $user, $assignment, int $assignment_id, int $question_id)
    {
        $response['type'] = 'error';
        $response['message'] = '';

        if (!$assignment->questions->contains($question_id)) {
            $response['message'] = 'No responses will be saved since that question is not in the assignment.';
            return $response;
        }

        if (!$assignment->course->enrollments->contains('user_id', $user->id)) {
            $response['message'] = 'No responses will be saved since the assignment is not part of your course.';
            return $response;
        }

        if (strtotime($assignment->available_from) > time()) {
            $response['message'] = 'No responses will be saved since this assignment is not yet available.';
            return $response;
        }

        if (!$assignment->assessment_type !== 'real time') {
            if ($assignment->show_scores) {
                $response['message'] = 'No responses will be saved since the scores to this assignment have been released.';
                return $response;
            }
            if ($assignment->solutions_released) {
                $response['message'] = 'No responses will be saved since the solutions to this assignment have been released.';
                return $response;
            }
        }
        if (time() > strtotime($assignment->due)) {
            //first let's see if there's an extension
            $extension = DB::table('extensions')
                ->select('extension')
                ->where('assignment_id', $assignment_id)
                ->where('user_id', $user->id)
                ->first('extension');
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
                if (strtotime($assignment->late_policy_deadline) < time()) {
                    $response['message'] = 'No more late responses are being accepted.';
                    return $response;
                }
            }
        }
        $response['type'] = 'success';
        return $response;
    }


}
