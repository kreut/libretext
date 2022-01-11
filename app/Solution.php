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
        $assignments_with_at_least_one_local_solution = DB::table('assignment_question')
            ->join('solutions', 'assignment_question.question_id','=','solutions.question_id')
            ->whereIn('assignment_question.assignment_id', $assignment_ids)
            ->where('user_id', $course->user_id)
            ->groupBy('assignment_question.assignment_id')
            ->get('assignment_question.assignment_id')
            ->pluck('assignment_id')
            ->toArray();

        if ($solutions->isNotEmpty()) {
            foreach ($solutions as $key => $value) {
                if ($solutions_released_by_assignment[$value->assignment_id] && in_array($value->assignment_id, $assignments_with_at_least_one_local_solution)) {
                  //only show the compiled if there's at least one PDF.  Otherwise, they're all local solutions
                    $solutions_by_assignment[$value->assignment_id] = $value->original_filename;
                }
            }
        }
        return $solutions_by_assignment;
    }

}
