<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShowSubmissionMessageToLearningNodeSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('show_submission_message')->default(0)->after('check_for_reset');
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
            $table->dropColumn('show_submission_message');
        });
    }
}
