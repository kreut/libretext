<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLearningObjectiveSyncNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('learning_objective_node', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_objective_id');
            $table->unsignedBigInteger('page_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('library',['bio','biz','chem','eng','espanol','geo','human','math','med','phys','socialsci','stats', 'workforce']);
            $table->unique(['page_id', 'learning_objective_id']);
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('learning_objective_sync_nodes');
    }
}
