<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAutoReleaseActivatedToAutoReleases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('auto_releases', function (Blueprint $table) {
            $table->unsignedTinyInteger('shown_activated')->after('shown')->nullable();
            $table->unsignedTinyInteger('show_scores_activated')->after('show_scores_after')->nullable();
            $table->unsignedTinyInteger('solutions_released_activated')->after('solutions_released_after')->nullable();
            $table->unsignedTinyInteger('students_can_view_assignment_statistics_activated')->after('students_can_view_assignment_statistics_after')->nullable();
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
            foreach (['shown_activated',
                         'show_scores_activated',
                         'solutions_released_activated',
                         'students_can_view_assignment_statistics_activated'] as $column) {
                $table->dropColumn($column);
            }
        });
    }
}
