<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashcardTtsLogsTable extends Migration
{
    public function up()
    {
        Schema::create('flashcard_tts_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_revision_id'); // 0 for brand-new questions with no revision row yet
            $table->string('side');
            $table->string('status');
            $table->string('s3_key')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('tts_model')->default('tts-1');
            $table->string('tts_voice')->default('nova');
            $table->timestamps();

            $table->foreign('question_id')->references('id')->on('questions');
            $table->unique(['question_id', 'question_revision_id', 'side']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('flashcard_tts_logs');
    }
}
