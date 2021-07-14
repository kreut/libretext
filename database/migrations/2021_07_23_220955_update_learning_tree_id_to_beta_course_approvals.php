<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLearningTreeIdToBetaCourseApprovals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('beta_course_approvals', function (Blueprint $table) {
           $table->unsignedBigInteger('beta_learning_tree_id')->after('beta_question_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('beta_course_approvals', function (Blueprint $table) {
            $table->dropColumn('beta_learning_tree_id');
        });
    }
}
