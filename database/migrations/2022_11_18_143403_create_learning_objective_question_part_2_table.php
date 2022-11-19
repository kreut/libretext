<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningObjectiveQuestionPart2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('learning_objective_question');
        Schema::create('learning_objective_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_objective_id');
            $table->unsignedBigInteger('question_id');
            $table->foreign('learning_objective_id')->references('id')->on('learning_objectives');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_objective_question');
    }
}
