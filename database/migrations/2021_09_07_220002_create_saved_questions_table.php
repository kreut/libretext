<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saved_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_question_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['assignment_question_id','user_id']);
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
        Schema::dropIfExists('saved_questions');
    }
}
