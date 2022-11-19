<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrameworkLearningObjectiveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('framework_level_learning_objective', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('framework_level_id');
            $table->unsignedBigInteger('learning_objective_id');
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
        Schema::dropIfExists('framework_level_learning_objective');
    }
}
