<?php

namespace App;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignmentGroup extends Model
{
    protected $guarded = [];

    /**
     * @param $default_assignment_groups
     * @param $other_assignment_groups
     * @return Collection
     */

    public function combine($default_assignment_groups, $other_assignment_groups): Collection
    {
        $assignment_groups = [];
        $used_assignment_groups = [];
        foreach ($default_assignment_groups as $default_assignment_group) {
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

    /**
     * @param int $role
     * @param $assignments
     * @param $total_points_by_assignment_id
     * @return array
     */
    public function summaryFromAssignments(int $role, $assignments, $total_points_by_assignment_id): array
    {
        $assignment_groups = [];
        $assignment_group_ids = [];
        $assignment_groups_by_id = [];
        $assignments_by_assignment_group_id = [];
        $include_in_total_points = [];
        foreach ($assignments as $value) {
            $include_assignment = $role == 2
                || ($role === 3 && $value->show_scores && $value->include_in_weighted_average);
            if ($include_assignment) {
                $assignments_by_assignment_group_id[$value->assignment_group_id][] = $value->id;
                $assignment_group_ids[] = $value->assignment_group_id;
            }
            if ($value->include_in_weighted_average) {
                $include_in_total_points[] = $value->id;
            }
        }

        $assignment_groups_info = DB::table('assignment_groups')
            ->select('id', 'assignment_group')
            ->whereIn('id', $assignment_group_ids)->get();
        foreach ($assignment_groups_info as $assignment_group) {
            $assignment_groups_by_id[$assignment_group->id] = $assignment_group->assignment_group;
        }

        foreach ($assignment_groups_info as $value) {
            $assignment_group_total_points = 0;
            foreach ($assignments_by_assignment_group_id[$value->id] as $assignment_id) {
                $total_points_for_assignment = $total_points_by_assignment_id[$assignment_id] ?? 0;
                //for students assignments are just included if scores are shown and value included in the weighted average (see above)
                //for instructors, all are included, but just sum if included in the weighted average
                if ($role === 3 || ($role === 2 && in_array($assignment_id, $include_in_total_points))) {
                    $assignment_group_total_points += $total_points_for_assignment;
                }
            }
            $assignment_groups[$value->id] = ['id' => $value->id,
                'assignment_group' => $assignment_groups_by_id[$value->id],
                'assignments' => $assignments_by_assignment_group_id[$value->id],
                'total_points' => Round(Helper::removeZerosAfterDecimal($assignment_group_total_points), 2)];
        }
        return $assignment_groups;
    }
}
