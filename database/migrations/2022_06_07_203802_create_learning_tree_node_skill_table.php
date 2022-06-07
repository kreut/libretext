<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningTreeNodeSkillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_tree_node_skill', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_tree_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('learning_tree_id')->references('id')->on('learning_trees');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('skill_id')->references('id')->on('skills');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_tree_node_skill');
    }
}
