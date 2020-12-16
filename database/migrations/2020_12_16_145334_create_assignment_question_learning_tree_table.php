<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentQuestionLearningTreeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_question_learning_tree', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_question_id');
            $table->unsignedBigInteger('learning_tree_id');
            $table->timestamps();
            $table->foreign('assignment_question_id')->references('id')->on('assignment_question');
            $table->foreign('learning_tree_id')->references('id')->on('learning_trees');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_learning_tree');
    }
}
