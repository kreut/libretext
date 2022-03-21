<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDefaultLearningTreeToAssignmentQuestionLearningTree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_learning_tree', function (Blueprint $table) {
            $table->string('learning_tree_success_level', 6)
                ->after('learning_tree_id');
            $table->string('learning_tree_success_criteria', 20)
                ->after('learning_tree_success_level');
            $table->unsignedSmallInteger('min_number_of_successful_branches')
                ->after('learning_tree_success_criteria')
                ->nullable();
            $table->unsignedSmallInteger('min_time_spent')
                ->after('min_number_of_successful_branches')
                ->nullable();
            $table->unsignedSmallInteger('min_number_of_successful_assessments')
                ->after('min_time_spent')
                ->nullable();
            $table->boolean('reset_points')
                ->after('min_number_of_successful_assessments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question_learning_tree', function (Blueprint $table) {
            $table->dropColumn(['learning_tree_success_level',
                'learning_tree_success_criteria',
                'min_number_of_successful_branches',
                'min_time_spent',
                'min_number_of_successful_assessments',
                'reset_points']);
        });
    }
}
