<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLibraryAndPageIdToLearningTreeHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('root_node_page_id')->after('learning_tree_id');
            $table->string('root_node_library')->after('root_node_page_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_histories', function (Blueprint $table) {
            //
        });
    }
}
