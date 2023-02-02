<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCheckForResetToLearningTreeNodeSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('check_for_reset')->default(0)->after('completed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_node_submissions', function (Blueprint $table) {
            $table->dropColumn('check_for_reset');
        });
    }
}
