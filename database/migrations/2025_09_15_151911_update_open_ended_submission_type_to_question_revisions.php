<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOpenEndedSubmissionTypeToQuestionRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->string('open_ended_submission_type')
                ->after('technology')
                ->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->dropColumn('open_ended_submission_type');
        });
    }
}
