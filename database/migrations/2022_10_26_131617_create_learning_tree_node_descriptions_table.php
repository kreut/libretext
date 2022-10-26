<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningTreeNodeDescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_tree_node_descriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_tree_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('notes', 10000);
            $table->timestamps();
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('learning_tree_id')->references('id')->on('learning_trees');
            $table->unique(['learning_tree_id', 'question_id', 'user_id'], 'tree_question_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learning_tree_node_descriptions');
    }
}
