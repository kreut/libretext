<?php


namespace App\Traits;


use App\Submission;
use App\User;
use Carbon\Carbon;
use App\Assignment;
use App\Extension;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait LatePolicy
{
    public function isLateSubmission(Extension $Extension, Assignment $assignment, Carbon $date_submitted)
    {
        $extension = $Extension->getAssignmentExtensionByUser($assignment, Auth::user());
        $max_date_to_submit = $extension ? $extension : $assignment->due;
        return $assignment->late_policy === 'marked late' && Carbon::parse($max_date_to_submit) < $date_submitted;
    }

    public function isLateSubmissionGivenExtensionForMarkedLatePolicy($extension, string $due, string $date_submitted)
    {
        $max_date_to_submit = $due = Carbon::parse($due);
        if ($extension) {
            $extension = Carbon::parse($extension);
            $max_date_to_submit = $extension->greaterThan($due) ? $extension : $due;
        }
        return $max_date_to_submit < Carbon::parse($date_submitted);
    }

    public function canSubmitBasedOnLatePolicy(User $user, $assignment, int $assignment_id, int $question_id)
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


        if (time() > strtotime($assignment->due)) {
            $extension = DB::table('extensions')
                ->select(DB::raw('UNIX_TIMESTAMP(extension) as extension'))
                ->where('assignment_id', $assignment_id)
                ->where('user_id', $user->id)
                ->first('extension');

            switch ($assignment->late_policy) {
                case('not accepted'):
                    if ($extension) {
                        if ($extension->extension < time()) {
                            $response['message'] = 'No responses will be saved since your extension for this assignment has passed.';
                            return $response;
                        }
                    } else {
                        $response['message'] = 'No responses will be saved since the due date for this assignment has passed.';
                        return $response;
                    }
                    break;
                case('deduction'):
                case('marked late'):
                    if (in_array($assignment->assessment_type, ['learning tree', 'delayed'])) {
                        if ($assignment->show_scores) {
                            $response['message'] = 'No responses will be saved since the scores to this assignment have been released.';
                            return $response;
                        }
                        if ($assignment->solutions_released) {
                            $response['message'] = 'No responses will be saved since the solutions to this assignment have been released.';
                            return $response;
                        }
                    }
                    //now let's check the late policy deadline
                    //if past policy deadline
                    if ($extension) {
                        if ($extension->extension < time()) {
                            $response['message'] = 'No responses will be saved since your extension for this assignment has passed.';
                            return $response;
                        }
                        if (strtotime($assignment->late_policy_deadline < time())) {
                            $response['message'] = 'No more late responses are being accepted.';
                            return $response;
                        }
                    } else {
                        if (strtotime($assignment->late_policy_deadline < time())) {
                            $response['message'] = 'No more late responses are being accepted.';
                            return $response;
                        } else {
                            $response['message'] = 'No responses will be saved since the due date for this assignment has passed.';
                            return $response;
                        }
                    }
                    break;
            }
        }
        $response['type'] = 'success';
        return $response;
    }


}
