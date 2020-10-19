<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Solution extends Model
{
    protected $guarded = [];

    public function getSolutionsByAssignment(Course $course)
    {

        $assignments = $course->assignments;
        $assignment_ids = [];
        $solutions_by_assignment = [];
        $solutions_released_by_assignment = [];
//initialize
        foreach ($assignments as $assignment) {
            $assignment_ids[] = $assignment->id;
            $solutions_by_assignment[$assignment->id] = false;
            $solutions_released_by_assignment[$assignment->id] = $assignment->solutions_released;

        }
        $solutions = DB::table('solutions')
            ->whereIn('assignment_id', $assignment_ids)
            ->where('user_id', $course->user_id)
            ->get();

        if ($solutions->isNotEmpty()) {
            foreach ($solutions as $key => $value) {
                if ($solutions_released_by_assignment[$value->assignment_id]) {
                    $solutions_by_assignment[$value->assignment_id] = $value->original_filename;
                }
            }
        }
        return $solutions_by_assignment;
    }

}
