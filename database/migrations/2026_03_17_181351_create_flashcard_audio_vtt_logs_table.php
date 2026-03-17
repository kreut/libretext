<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlashcardAudioVttLogsTable extends Migration
{
    public function up()
    {
        Schema::create('flashcard_audio_vtt_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_revision_id');
            $table->enum('side', ['front', 'back']);
            $table->enum('status', ['processing', 'success', 'error']);
            $table->string('s3_key')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('model')->default('whisper-1');
            $table->timestamps();

            $table->unique(['question_id', 'question_revision_id', 'side'], 'fc_audio_vtt_logs_unique');
            $table->index('question_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('flashcard_audio_vtt_logs');
    }
}
