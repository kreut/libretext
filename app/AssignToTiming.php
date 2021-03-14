<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AssignToTiming extends Model
{
    use HasFactory;

    public function assignToUsers()
    {
        return $this->hasMany(AssignToUser::class);
    }

    public function assignToGroups()
    {
        return $this->hasMany(AssignToGroup::class);
    }

    public function deleteTimingsGroupsUsers(Assignment $assignment)
    {
        $assignToTimings = $assignment->assignToTimings;
        foreach ($assignToTimings as $assignToTiming) {
            DB::table('assign_to_groups')
                ->where('assign_to_timing_id', $assignToTiming->id)
                ->delete();
            DB::table('assign_to_users')
                ->where('assign_to_timing_id', $assignToTiming->id)
                ->delete();
            DB::table('assign_to_timings')
                ->where('id', $assignToTiming->id)
                ->delete();
        }
    }

}
