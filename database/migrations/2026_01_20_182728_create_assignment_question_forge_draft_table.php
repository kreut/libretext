<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentQuestionForgeDraftTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_question_forge_draft', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_question_id');
            $table->string('forge_draft_id');
            $table->timestamps();
            $table->foreign('assignment_question_id')->references('id')->on('assignment_question');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_question_forge_draft');
    }
}
