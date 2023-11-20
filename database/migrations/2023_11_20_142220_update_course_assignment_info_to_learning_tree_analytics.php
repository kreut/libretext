<?php

use App\LearningTreeAnalytics;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateCourseAssignmentInfoToLearningTreeAnalytics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_analytics', function (Blueprint $table) {
            $table->string('course_name')->after('id');
        });
        Schema::table('learning_tree_analytics', function (Blueprint $table) {
            $table->string('instructor')->after('course_name');
        });
        Schema::table('learning_tree_analytics', function (Blueprint $table) {
            $table->string('assignment_name')->after('instructor');
        });
        Schema::table('learning_tree_analytics', function (Blueprint $table) {
            $learning_tree_analytics = LearningTreeAnalytics::get();
            foreach ($learning_tree_analytics as $value) {
                $info = DB::table('learning_tree_analytics')
                    ->join('assignments', 'learning_tree_analytics.assignment_id', '=', 'assignments.id')
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->join('users', 'courses.user_id', '=', 'users.id')
                    ->select('courses.name AS course_name',
                        'assignments.name AS assignment_name',
                        DB::raw('CONCAT(first_name, " " , last_name) AS instructor')
                    )
                    ->where('learning_tree_analytics.id', $value->id)
                    ->first();
                $value->assignment_name = $info->assignment_name;
                $value->course_name = $info->course_name;
                $value->instructor = $info->instructor;
                $value->save();
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_analytics', function (Blueprint $table) {
            $table->dropColumn(['assignment_name', 'course_name', 'instructor']);
        });
    }
}
