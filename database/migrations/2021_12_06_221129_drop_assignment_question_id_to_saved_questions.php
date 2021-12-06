<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAssignmentQuestionIdToSavedQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('saved_questions', function (Blueprint $table) {
            $table->dropForeign('saved_questions_assignment_question_id_foreign');
            $table->dropIndex('saved_questions_assignment_question_id_user_id_unique');
            $table->dropColumn('assignment_question_id');
            $table->unique(['user_id','question_id']);
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
            //
        });
    }
}
