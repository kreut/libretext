<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToRemediationSubmissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('remediation_submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('branch_id')->after('learning_tree_id');
            $table->unique(['user_id','assignment_id','learning_tree_id','branch_id','question_id'], 'remediation_submission_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('remediation_submissions', function (Blueprint $table) {
            $table->dropUnique('remediation_submission_unique');
            $table->dropColumn('branch_id');
        });
    }
}
