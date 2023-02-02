<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningTreeColumnsToAssignmentTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_templates', function (Blueprint $table) {

            $table->dropColumn(['learning_tree_success_level',
                'learning_tree_success_criteria',
                'number_of_resets',
                'min_number_of_successful_assessments',
                'free_pass_for_satisfying_learning_tree_criteria',
                'min_time_needed_in_learning_tree',
                'percent_earned_for_exploring_learning_tree',
                'submission_count_percent_decrease',
                'min_time']);

        });
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->renameColumn('number_of_successful_branches_for_a_reset', 'number_of_successful_paths_for_a_reset');
        });
        Schema::table('assignment_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_number_of_minutes_in_exposition_node')
                ->after('number_of_successful_paths_for_a_reset')
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
        Schema::table('assignment_templates', function (Blueprint $table) {
            //
        });
    }
}
