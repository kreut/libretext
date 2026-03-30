<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionWebworkMacrosTable extends Migration
{
    public function up()
    {
        Schema::create('question_webwork_macros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            // 0 means no revision yet (question has never been edited after initial creation)
            $table->unsignedBigInteger('question_revision_id')->default(0);
            $table->unsignedBigInteger('webwork_macro_id');
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            $table->foreign('webwork_macro_id')->references('id')->on('webwork_macros')->onDelete('restrict');

            $table->unique(
                ['question_id', 'question_revision_id', 'webwork_macro_id'],
                'unique_question_revision_macro'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('question_webwork_macros');
    }
}
