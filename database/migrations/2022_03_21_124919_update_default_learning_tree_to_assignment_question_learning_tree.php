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
            $table->unsignedSmallInteger('min_time')
                ->after('min_number_of_successful_branches')
                ->nullable();
            $table->unsignedSmallInteger('min_number_of_successful_assessments')
                ->after('min_time')
                ->nullable();
            $table->boolean('free_pass_for_satisfying_learning_tree_criteria')
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
                'min_time',
                'min_number_of_successful_assessments',
                'free_pass_for_satisfying_learning_tree_criteria']);
        });
    }
}
