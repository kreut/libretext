<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningTreeOutcomeIdToLearningTreeNodeLearningOutcome extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_learning_outcome', function (Blueprint $table) {
            $table->renameColumn('learning_tree_outcome_id','learning_outcome_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_node_learning_outcome', function (Blueprint $table) {
            //
        });
    }
}
