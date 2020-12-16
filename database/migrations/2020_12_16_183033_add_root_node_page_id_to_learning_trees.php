<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRootNodePageIdToLearningTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->unsignedBigInteger('root_node_page_id')->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->dropColumn('root_node_page_id');
        });
    }
}
