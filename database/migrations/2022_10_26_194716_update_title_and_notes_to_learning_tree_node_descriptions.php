<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTitleAndNotesToLearningTreeNodeDescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_descriptions', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->string('notes', 10000)->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_node_descriptions', function (Blueprint $table) {
            //
        });
    }
}
