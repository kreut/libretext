<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAppliedToResetToLearningTreeSuccessfulBranches extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('learning_tree_successful_branches', function (Blueprint $table) {
           $table->unsignedTinyInteger('applied_to_reset')->after('branch_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('learning_tree_successful_branches', function (Blueprint $table) {
            $table->dropColumn('applied_to_reset');
        });
    }
}
