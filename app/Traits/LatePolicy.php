<?php


namespace App\Traits;


use Carbon\Carbon;
use App\Assignment;
use App\Extension;
use Illuminate\Support\Facades\Auth;

trait LatePolicy
{
    public function isLateSubmission(Extension $Extension, Assignment $assignment){
        $extension = $Extension->getAssignmentExtensionByUser($assignment, Auth::user());
        $due = $extension ? $extension : $assignment->due;
        return $assignment->late_policy === 'marked late' && Carbon::parse($due) < Carbon::parse(now());
    }
}
