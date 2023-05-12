<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingQuestionRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_question_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_revision_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('question_id');
            $table->string('status',10);
            $table->timestamps();
            $table->foreign('question_revision_id')->references('id')->on('question_revisions');
            $table->foreign('assignment_id')->references('id')->on('assignments');
            $table->foreign('question_id')->references('id')->on('questions');
            $table->unique(['assignment_id','question_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pending_question_revisions');
    }
}
