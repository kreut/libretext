<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubmissionCountProportionCorrectToLearningTreeNodeSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_node_submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('submission_count')->nullable()->default(0)->after('submission');
            $table->decimal('proportion_correct', 8, 4)->nullable()->after('submission');
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
            $table->dropColumn(['submission_count','proportion_correct']);
        });
    }
}
