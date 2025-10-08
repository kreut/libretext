<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuestionSubjectChapterSectionIdToQuestionRevisions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->string('question_subject_id')->after('title')->nullable();
        });
        Schema::table('question_revisions', function (Blueprint $table) {
            $table->string('question_chapter_id')->after('question_subject_id')->nullable();
        });

        Schema::table('question_revisions', function (Blueprint $table) {
            $table->string('question_section_id')->after('question_chapter_id')->nullable();
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
            $table->dropColumn(['question_subject_id', 'question_chapter_id', 'question_section_id']);
        });
    }
}
