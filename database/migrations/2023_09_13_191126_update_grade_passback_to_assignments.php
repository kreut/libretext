<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateGradePassbackToAssignments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('lms_grade_passback')->after('notifications')->nullable();
        });
        $lms_assignment_ids = DB::table('courses')
            ->join('assignments', 'courses.id', '=', 'assignments.course_id')
            ->where('courses.lms', 1)
            ->select("assignments.id")
            ->pluck('id')
            ->toArray();

        DB::table('assignments')
            ->whereIn('id', $lms_assignment_ids)
            ->update(['lms_grade_passback' => 'automatic']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('lms_grade_passback');
        });
    }
}
