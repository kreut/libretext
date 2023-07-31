<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDescriptionToLearningTreeNodeDescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_descriptions', function (Blueprint $table) {
          $table->string('description',1000)->after('title')->nullable();
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
            $table->dropColumn('description');

        });
    }
}
