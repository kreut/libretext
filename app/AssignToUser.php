<?php

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class AssignToUser extends Model
{


    public function assignToTimingsAndAssignmentsByAssignmentIdByCourse(int $course_id)
    {
        return DB::table('assign_to_users')
            ->join('assign_to_timings','assign_to_users.assign_to_timing_id','=','assign_to_timings.id')
            ->join('assignments', 'assign_to_timings.assignment_id', '=', 'assignments.id')
            ->where('assignments.course_id', $course_id)
            ->select('assign_to_timings.id AS assign_to_timing_id', 'assignments.id AS assignment_id')
            ->get();
    }

    public function assignToUserForAssignments( Collection $assignments, int $user_id, int $section_id)
    {

        $assign_to_timing_ids = [];

        foreach ($assignments as $assignment) {
            $assignToTimings = $assignment->assignToTimings;
            foreach ($assignToTimings as $assignToTiming) {
                foreach ($assignToTiming->assignToGroups as $assignToGroup) {

                    if ($assignToGroup->group === 'section' && $assignToGroup->group_id === $section_id) {
                        if (in_array($assignToTiming->id, $assign_to_timing_ids)) {
                            continue;
                        }
                        $assignToUser = new AssignToUser();
                        $assignToUser->assign_to_timing_id = $assignToTiming->id;
                        $assignToUser->user_id = $user_id;
                        $assignToUser->save();
                        $assign_to_timing_ids[] = $assignToTiming->id;
                    }
                    if ($assignToGroup->group === 'course') {
                        if (in_array($assignToTiming->id, $assign_to_timing_ids)) {
                            continue;
                        }
                        $assignToUser = new AssignToUser();
                        $assignToUser->assign_to_timing_id = $assignToTiming->id;
                        $assignToUser->user_id = $user_id;
                        $assign_to_timing_ids[] = $assignToTiming->id;
                        $assignToUser->save();
                    }
                }
            }
        }
    }

}
