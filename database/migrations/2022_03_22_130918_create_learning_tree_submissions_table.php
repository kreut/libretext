<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningTreeSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_tree_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('learning_tree_id');
            $table->unsignedTinyInteger('branch_id');
            $table->unsignedBigInteger('branch_question_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->text('submission')->nullable();
            $table->unsignedSmallInteger('time_spent')->nullable();
            $table->boolean('answered_correctly_at_least_once');
            $table->timestamps();

            $table->unique(['user_id','assignment_id','question_id'], 'learning_tree_submission_unique');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('learning_tree_id')->references('id')->on('learning_trees');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('branch_question_id')->references('id')->on('questions');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_tree_submissions');
    }
}
