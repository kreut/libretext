<?php

use App\Assignment;
use App\AssignToGroup;
use App\AssignToTiming;
use App\AssignToUser;
use App\Course;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveAssignmentInfoToAssignTos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $assignments = Assignment::all();
        foreach ($assignments as $assignment) {
            $assignToTiming = new AssignToTiming();
            $assignToTiming->assignment_id = $assignment->id;
            $assignToTiming->available_from = $assignment->available_from;
            $assignToTiming->due = $assignment->due;
            $assignToTiming->final_submission_deadline = $assignment->final_submission_deadline;
            $assignToTiming->save();
            $assignToGroup = new AssignToGroup();
            $assignToGroup->assign_to_timing_id = $assignToTiming->id;
            $assignToGroup->group = 'course';
            $assignToGroup->group_id = $assignment->course->id;
            $assignToGroup->save();

            $course = Course::find($assignment->course->id);
            $enrollments = $course->enrollments;
            foreach ($enrollments as $enrollment) {
                $assignToUser = new AssignToUser();
                $assignToUser->assign_to_timing_id = $assignToTiming->id;
                $assignToUser->user_id = $enrollment->user_id;
                $assignToUser->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assign_tos', function (Blueprint $table) {
            //
        });
    }
}
