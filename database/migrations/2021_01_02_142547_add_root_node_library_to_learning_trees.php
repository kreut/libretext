<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRootNodeLibraryToLearningTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->string('root_node_library')->after('root_node_page_id');

        });
        DB::table('learning_trees')->update(['root_node_library'=> 'query']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_trees', function (Blueprint $table) {
            $table->dropColumn('root_node_library');
        });
    }
}
