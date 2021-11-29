<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateAssignmentQuestionDetailToSavedQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_id')->after('user_id')->nullable();
            $table->string('open_ended_submission_type')->after('question_id');
            $table->string('open_ended_text_editor')->after('open_ended_submission_type')->nullable();
            $table->unsignedBigInteger('learning_tree_id')->after('open_ended_text_editor')->nullable();
        });

        Schema::table('saved_questions', function (Blueprint $table) {
            $table->foreign('question_id')->nullable()->references('id')->on('questions');
            $table->foreign('learning_tree_id')->nullable()->references('id')->on('learning_trees');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->dropColumn(['question_id','open_ended_submission_type','open_ended_text_editor','learning_tree_id']);
        });
    }
}
