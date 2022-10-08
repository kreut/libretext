<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateNotesVersionToAssignmentQuestionCaseStudyNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assignment_question_case_study_notes', function (Blueprint $table) {
            $table->string('version_of_notes',19)->after('case_study_notes_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assignment_question_case_study_notes', function (Blueprint $table) {
            $table->dropColumn('version_of_notes');
        });
    }
}
