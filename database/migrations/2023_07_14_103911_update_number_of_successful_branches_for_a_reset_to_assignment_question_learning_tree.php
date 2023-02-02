<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNumberOfSuccessfulBranchesForAResetToAssignmentQuestionLearningTree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('assignment_question_learning_tree', function (Blueprint $table) {
            $table->dropColumn(['learning_tree_success_level',
                'learning_tree_success_criteria',
                'min_time',
                'number_of_resets',
                'min_number_of_successful_assessments',
                'free_pass_for_satisfying_learning_tree_criteria']);
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
            //
        });
    }
}
