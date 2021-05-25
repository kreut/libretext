<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningTreeHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_tree_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_tree_id');
            $table->text('learning_tree');
            $table->timestamps();
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
        Schema::dropIfExists('learning_tree_histories');
    }
}
