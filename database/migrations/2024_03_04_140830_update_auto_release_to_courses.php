<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutoReleaseToCourses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('auto_release_shown')->nullable();
            $table->string('auto_release_show_scores')->nullable();
            $table->string('auto_release_solutions_released')->nullable();
            $table->string('auto_release_students_can_view_assignment_statistics')->nullable();
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
            foreach (['auto_release_shown',
                         'auto_release_show_scores',
                         'auto_release_solutions_released',
                         'auto_release_students_can_view_assignment_statistics'] as $col) {
                $table->dropColumn($col);
            }
        });
    }
}
