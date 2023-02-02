<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameNumberOfSuccessfulBranchesForAResetToAssignmentQuestionLearningTree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_learning_tree', function (Blueprint $table) {
            $table->renameColumn('number_of_successful_branches_for_a_reset','number_of_successful_paths_for_a_reset');
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
