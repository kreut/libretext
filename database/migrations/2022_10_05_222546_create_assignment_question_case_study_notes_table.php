<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentQuestionCaseStudyNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_question_case_study_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('case_study_notes_id');
            $table->timestamps();
            $table->unique(['assignment_id', 'question_id', 'case_study_notes_id'],'assignment_question_case_study_notes_unique');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->foreign('case_study_notes_id')->references('id')->on('case_study_notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_question_case_study_notes');
    }
}
