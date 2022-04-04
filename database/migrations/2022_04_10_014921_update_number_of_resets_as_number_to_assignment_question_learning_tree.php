<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNumberOfResetsAsNumberToAssignmentQuestionLearningTree extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_learning_tree', function (Blueprint $table) {
            $table->unsignedSmallInteger('number_of_resets')->change();
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
            $table->string('number_of_resets')->change();
        });
    }
}
