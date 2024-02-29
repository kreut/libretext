<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShowAfterToAutoReleases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('auto_releases', function (Blueprint $table) {
            $table->string('show_scores_after')->after('show_scores')->nullable();
            $table->string('solutions_released_after')->after('solutions_released')->nullable();
            $table->string('students_can_view_assignment_statistics_after')->after('students_can_view_assignment_statistics')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('auto_releases', function (Blueprint $table) {
            foreach (['show_scores_after', 'solutions_released_after', 'students_can_view_assignment_statistics_after'] as $col) {
                $table->dropColumn($col);
            }
        });
    }
}
