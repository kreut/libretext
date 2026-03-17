<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

abstract class FlashcardAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $questionId;

    /**
     * @var int  0 means no revision row exists yet (brand-new question)
     */
    protected $questionRevisionId;

    public function __construct(int $questionId, int $questionRevisionId)
    {
        $this->questionId = $questionId;
        $this->questionRevisionId = $questionRevisionId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $question = Question::find($this->questionId);

        if (!$question || $question->qti_json_type !== 'flashcard') {
            return;
        }

        if ($this->questionRevisionId) {
            $revision = QuestionRevision::find($this->questionRevisionId);
            $qtiJson = $revision ? $revision->qti_json : $question->qti_json;
        } else {
            $revision = null;
            $qtiJson = $question->qti_json;
        }

        $qti = json_decode($qtiJson, true);
        $card = $qti['card'] ?? [];

        $sides = $this->getSidesToProcess($card);

        if (empty($sides)) {
            return;
        }

        foreach ($sides as $side => $payload) {
            $s3Key = $this->processForSide($side, $payload);
            if ($s3Key) {
                $this->writeS3KeyToQtiJson($question, $revision, $side, $s3Key);
            }
        }
    }

    /**
     * Determine which sides need processing.
     * Returns an array keyed by side ('front'/'back') with whatever payload
     * the subclass needs (text for TTS, audio S3 key for VTT).
     *
     * @param array $card
     * @return array
     */
    abstract protected function getSidesToProcess(array $card): array;

    /**
     * Perform the actual AI call for one side and store the result on S3.
     * Returns the S3 key on success, or null on failure.
     *
     * @param string $side    'front' or 'back'
     * @param mixed  $payload Whatever getSidesToProcess() put in the array
     * @return string|null
     */
    abstract protected function processForSide(string $side, $payload): ?string;

    /**
     * The job_type value for the log table ('tts' or 'vtt').
     */
    abstract protected function jobType(): string;

    /**
     * Write an S3 key back into qti_json on both the question and revision rows.
     *
     * @param Question              $question
     * @param QuestionRevision|null $revision
     * @param string                $side     'front' or 'back'
     * @param string                $s3Key
     */
    protected function writeS3KeyToQtiJson(Question $question, ?QuestionRevision $revision, string $side, string $s3Key): void
    {
        $jsonKey = $this->s3JsonKey($side);

        $qti = json_decode($question->qti_json, true);
        $qti['card'][$jsonKey] = $s3Key;
        $question->qti_json = json_encode($qti);
        $question->save();

        if ($revision) {
            $revQti = json_decode($revision->qti_json, true);
            $revQti['card'][$jsonKey] = $s3Key;
            $revision->qti_json = json_encode($revQti);
            $revision->save();
        }
    }

    /**
     * The qti_json card key used to store the generated S3 key.
     * e.g. 'frontTtsS3Key', 'backMediaVttS3Key'
     */
    abstract protected function s3JsonKey(string $side): string;

    /**
     * Upsert a row in flashcard_ai_audio_logs.
     */
    protected function upsertLog(
        string  $side,
        string  $status,
        ?string $s3Key,
        ?string $errorMessage,
        ?int    $durationMs,
        string  $model,
        ?string $voice = null
    ): void
    {
        DB::table('flashcard_ai_audio_logs')->updateOrInsert(
            [
                'question_id'          => $this->questionId,
                'question_revision_id' => $this->questionRevisionId,
                'side'                 => $side,
                'job_type'             => $this->jobType(),
            ],
            [
                'status'        => $status,
                's3_key'        => $s3Key,
                'error_message' => $errorMessage,
                'duration_ms'   => $durationMs,
                'model'         => $model,
                'voice'         => $voice,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );
    }
}
