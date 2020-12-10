<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssignmentGroup extends Model
{
    protected $fillable = ['assignment_group'];

    public function assignmentGroupsByCourse(int $course_id)
    {
        $results = DB::table('assignments')
            ->join('assignment_groups', 'assignment_group_id', 'assignment_groups.id')
            ->where('assignments.course_id', $course_id)
            ->select('assignments.id', 'assignment_group')
            ->get();
        $assignment_groups_by_assignment = [];
        if ($results->isNotEmpty()) {
            foreach ($results as $key => $value) {
                $assignment_groups_by_assignment[$value->id] = $value->assignment_group;
            }
        }
        return $assignment_groups_by_assignment;
    }
}
