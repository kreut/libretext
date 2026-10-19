<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionPageAnnotationsTable extends Migration
{
    /**
     * A grader's markup (marker.js 3 state) on one page of one student's submission to one question.
     *
     * Keyed by question as well as page: two questions can share a page of a compiled PDF, and each
     * question's grader marks it up separately.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission_page_annotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id'); // the student
            $table->unsignedSmallInteger('page');
            $table->longText('state'); // JSON from MarkerArea.getState(), at the page image's natural size
            $table->unsignedBigInteger('grader_id')->nullable(); // who last saved it
            $table->timestamps();

            $table->unique(['assignment_id', 'question_id', 'user_id', 'page'], 'submission_page_annotations_unique');
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('grader_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('submission_page_annotations');
    }
}
