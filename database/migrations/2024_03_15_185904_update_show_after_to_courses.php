<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShowAfterToCourses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('auto_release_show_scores_after')->after('auto_release_show_scores')->nullable();
            $table->string('auto_release_solutions_released_after')->after('auto_release_solutions_released')->nullable();
            $table->string('auto_release_students_can_view_assignment_statistics_after')->after('auto_release_students_can_view_assignment_statistics')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('courses', function (Blueprint $table) {
            foreach (['auto_release_show_scores_after',
                         'auto_release_solutions_released_after',
                         'auto_release_students_can_view_assignment_statistics_after'] as $col) {
                $table->dropColumn($col);
            }
        });
    }

}
