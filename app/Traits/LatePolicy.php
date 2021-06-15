<?php


namespace App\Traits;

use Carbon\Carbon;
use App\Assignment;
use App\Extension;
use Illuminate\Support\Facades\Auth;

trait LatePolicy
{
    public function isLateSubmission($Extension, Assignment $assignment, Carbon $date_submitted)
    {
        if ($Extension === false){
            //checked at the individual level and it's not an extension
            return false;
        }
        //either pass through the model because we don't actually know it or pass through an actual extension.
        $extension = ($Extension instanceof Extension)
            ? $Extension->getAssignmentExtensionByUser($assignment, Auth::user())
            : $Extension;

        $max_date_to_submit = $extension ?: $assignment->assignToTimingByUser('due');
        return in_array($assignment->late_policy, ['marked late', 'deduction']) && Carbon::parse($max_date_to_submit) < $date_submitted;
    }

    public function isLateSubmissionGivenExtensionForMarkedLatePolicy($extension, string $due, string $date_submitted)
    {
        $max_date_to_submit = $due = Carbon::parse($due);
        $date_submitted = Carbon::parse($date_submitted);
        if ($extension) {
            $extension = Carbon::parse($extension);
            $max_date_to_submit = $extension->greaterThan($due) ? $extension : $due;
        }

        if ($max_date_to_submit < $date_submitted) {
            if ($date_submitted->DiffInDays($max_date_to_submit) >= 1) {
                $late = Round($date_submitted->floatDiffInDays($max_date_to_submit), 2);
                return "$late days";
            }

            if ($date_submitted->DiffInHours($max_date_to_submit) >= 1) {
                $late = Round($date_submitted->floatDiffInHours($max_date_to_submit), 2);
                return "$late hours";
            }
            if ($date_submitted->DiffInMinutes($max_date_to_submit) >= 1) {
                $late = Round($date_submitted->floatDiffInMinutes($max_date_to_submit), 2);
                return "$late minutes";
            }
            $late = Round($date_submitted->floatDiffInHours($max_date_to_submit), 2);
            return "$late seconds";
        } else {
            return false;
        }
    }


}
