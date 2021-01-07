<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOpenEndedSubmissionTypeToAssignmentQuestion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->string('open_ended_submission_type')->after('question_id');
            $table->dropColumn('question_files');
        });
        DB::table('assignment_question')->update(['open_ended_submission_type' => 'file']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question', function (Blueprint $table) {
            $table->dropColumn('open_ended_submission_type');
            $table->boolean('question_files');
        });
        DB::table('assignment_question')->update(['question_files' => 1]);
    }
}
