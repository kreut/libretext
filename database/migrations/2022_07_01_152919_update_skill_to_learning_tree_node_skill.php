<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSkillToLearningTreeNodeSkill extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_skill', function (Blueprint $table) {
            $table->renameColumn('skill_id','learning_tree_outcome_id');
            $table->rename('learning_tree_node_learning_outcome');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_node_skill', function (Blueprint $table) {
            //
        });
    }
}
