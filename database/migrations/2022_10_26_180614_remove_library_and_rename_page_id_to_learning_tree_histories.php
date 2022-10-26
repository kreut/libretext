<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLibraryAndRenamePageIdToLearningTreeHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_histories', function (Blueprint $table) {
            $table->renameColumn('root_node_page_id', 'root_node_question_id');
            $table->dropColumn('root_node_library');
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
