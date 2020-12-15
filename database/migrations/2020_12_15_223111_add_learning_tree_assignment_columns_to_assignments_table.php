<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLearningTreeAssignmentColumnsToAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            //
            $table->smallInteger('min_time_needed_in_learning_tree')
                    ->after('assessment_type')
                    ->nullable();
            $table->smallInteger('percent_earned_for_entering_learning_tree')
                ->after('min_time_needed_in_learning_tree')
                ->nullable();
            $table->smallInteger('percent_decay')
                ->after('percent_earned_for_entering_learning_tree')
                ->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['min_time_needed_in_learning_tree','percent_earned_for_entering_learning_tree','percent_decay']);
        });
    }
}
