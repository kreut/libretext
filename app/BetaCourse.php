<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BetaCourse extends Model
{
    protected $guarded = [];

    public function untether(Course $course)
    {
        $beta_assignment_ids = $course->assignments->pluck('id')->toArray();
        BetaCourseApproval::whereIn('id', $beta_assignment_ids)->delete();
        BetaAssignment::whereIn('id', $beta_assignment_ids)->delete();
        BetaCourse::where('id', $course->id)->delete();
    }
}
