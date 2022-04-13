<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentGroup extends Model
{
    protected $fillable = ['assignment_group'];

    /**
     * @param $default_assignment_groups
     * @param $other_assignment_groups
     * @return Collection
     */

    public function combine($default_assignment_groups, $other_assignment_groups): Collection
    {
        $assignment_groups = [];
        $used_assignment_groups = [];
        foreach ($default_assignment_groups as  $default_assignment_group) {
            $assignment_groups[] = $default_assignment_group;
            $used_assignment_groups[] = $default_assignment_group->assignment_group;
        }

        foreach ($other_assignment_groups as $key => $other_assignment_group) {
            if (!in_array($other_assignment_group->assignment_group, $used_assignment_groups)) {
                $assignment_groups[] = $other_assignment_group;
                $used_assignment_groups[] = $other_assignment_group->assignment_group;
            }
        }
        return collect($assignment_groups);
    }

    public function importAssignmentGroupToCourse(Course $course, Assignment $assignment)
    {
        $assignment_group = AssignmentGroup::find($assignment->assignment_group_id);

        $imported_assignment_group = DB::table('assignment_groups')
            ->where('user_id', $assignment_group->user_id)
            ->where('assignment_group', $assignment_group->assignment_group)
            ->where('course_id', $course->id)
            ->first();
        $default_assignment_group = DB::table('assignment_groups')
            ->where('user_id', 0)
            ->where('assignment_group', $assignment_group->assignment_group)
            ->first();

        if ($default_assignment_group) {
            $imported_assignment_group_id = $default_assignment_group->id;
        } else {
            //don't have it in your course yet and it's not one of the default ones
            if (!$imported_assignment_group) {
                $imported_assignment_group = $assignment_group->replicate();
                $imported_assignment_group->course_id = $course->id;
                $imported_assignment_group->save();
            }
            $imported_assignment_group_id = $imported_assignment_group->id;
        }
        return $imported_assignment_group_id;
    }

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
