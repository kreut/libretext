<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateFlashcardAiAudioLogsTable extends Migration
{
    public function up()
    {
        Schema::create('flashcard_ai_audio_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_revision_id');
            $table->enum('side', ['front', 'back']);
            $table->enum('job_type', ['tts', 'vtt']);
            $table->enum('status', ['processing', 'success', 'error']);
            $table->string('s3_key')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('model');
            $table->string('voice')->nullable(); // TTS only
            $table->timestamps();

            $table->unique(
                ['question_id', 'question_revision_id', 'side', 'job_type'],
                'fc_ai_audio_logs_unique'
            );
            $table->index('question_id');
        });

        // Now migrate existing TTS log data and drop the old table
        if (Schema::hasTable('flashcard_tts_logs')) {
            DB::statement("
                INSERT INTO flashcard_ai_audio_logs
                    (question_id, question_revision_id, side, job_type, status, s3_key,
                     error_message, duration_ms, model, voice, created_at, updated_at)
                SELECT
                    question_id, question_revision_id, side, 'tts', status, s3_key,
                    error_message, duration_ms, tts_model, tts_voice, created_at, updated_at
                FROM flashcard_tts_logs
            ");
            Schema::drop('flashcard_tts_logs');
        }
    }

    public function down()
    {
        // Restore the old flashcard_tts_logs table
        Schema::create('flashcard_tts_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_revision_id');
            $table->enum('side', ['front', 'back']);
            $table->enum('status', ['processing', 'success', 'error']);
            $table->string('s3_key')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('tts_model');
            $table->string('tts_voice')->nullable();
            $table->timestamps();

            $table->unique(
                ['question_id', 'question_revision_id', 'side'],
                'fc_tts_logs_unique'
            );
            $table->index('question_id');
        });

        DB::statement("
            INSERT INTO flashcard_tts_logs
                (question_id, question_revision_id, side, status, s3_key,
                 error_message, duration_ms, tts_model, tts_voice, created_at, updated_at)
            SELECT
                question_id, question_revision_id, side, status, s3_key,
                error_message, duration_ms, model, voice, created_at, updated_at
            FROM flashcard_ai_audio_logs
            WHERE job_type = 'tts'
        ");

        Schema::dropIfExists('flashcard_ai_audio_logs');
    }
}
