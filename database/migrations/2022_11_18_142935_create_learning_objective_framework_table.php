<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningObjectiveFrameworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_objective_question', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('framework_level_id');
            $table->unsignedBigInteger('learning_objective_id');
            $table->unique(['framework_level_id','learning_objective_id'],'framework_level_learning_objective_unique');
            $table->foreign('framework_level_id')->references('id')->on('framework_levels');
            $table->foreign('learning_objective_id')->references('id')->on('learning_objectives');
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
