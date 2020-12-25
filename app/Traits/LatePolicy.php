<?php


namespace App\Traits;

use Carbon\Carbon;
use App\Assignment;
use App\Extension;
use Illuminate\Support\Facades\Auth;

trait LatePolicy
{
    public function isLateSubmission(Extension $Extension, Assignment $assignment, Carbon $date_submitted)
    {
        $extension = $Extension->getAssignmentExtensionByUser($assignment, Auth::user());
        $max_date_to_submit = $extension ? $extension : $assignment->due;
        return in_array($assignment->late_policy,['marked late','deduction']) && Carbon::parse($max_date_to_submit) < $date_submitted;
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


}
