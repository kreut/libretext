<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use App\Question;
use App\QuestionRevision;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $question = Question::find($this->questionId);

        if (!$question || $question->qti_json_type !== 'flashcard') {
            return;
        }
        $s3_keys_of_previously_generated_audio = [];
        if ($this->questionRevisionId) {
            $revision = QuestionRevision::find($this->questionRevisionId);
            if ($revision && $this->jobType() === 'tts') {
                $revision_number = $revision->revision_number;
                $last_revision = QuestionRevision::where('question_id', $this->questionId)
                    ->where('revision_number', $revision_number - 1)
                    ->first();
                if ($last_revision) {
                    $s3_keys_of_previously_generated_audio = $this->_s3KeysOfPreviouslyGeneratedAudio($question, $last_revision, $this->questionRevisionId);
                }
            }
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
            $language = '';
            if ($this->jobType() === 'tts' && in_array($card["{$side}Type"], ['text_only', 'text_media'])) {
                $language = $card["{$side}TTSLanguage"];
            }
            $s3Key = $s3_keys_of_previously_generated_audio[$side] ?? $this->processForSide($side, $payload, $language);
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
     * @param string $side 'front' or 'back'
     * @param mixed $payload Whatever getSidesToProcess() put in the array
     * @param string $language
     * @return string|null
     */
    abstract protected function processForSide(string $side, $payload, string $language = ''): ?string;

    /**
     * The job_type value for the log table ('tts' or 'vtt').
     */
    abstract protected function jobType(): string;

    /**
     * Write an S3 key back into qti_json on both the question and revision rows.
     *
     * @param Question $question
     * @param QuestionRevision|null $revision
     * @param string $side 'front' or 'back'
     * @param string $s3Key
     */
    protected function writeS3KeyToQtiJson(Question $question, ?QuestionRevision $revision, string $side, string $s3Key): void
    {
        $jsonKey = $this->s3JsonKey($side);

        DB::statement(
            "UPDATE questions SET qti_json = JSON_SET(qti_json, '$.card.{$jsonKey}', ?) WHERE id = ?",
            [$s3Key, $question->id]
        );

        if ($revision) {
            DB::statement(
                "UPDATE question_revisions SET qti_json = JSON_SET(qti_json, '$.card.{$jsonKey}', ?) WHERE id = ?",
                [$s3Key, $revision->id]
            );
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
                'question_id' => $this->questionId,
                'question_revision_id' => $this->questionRevisionId,
                'side' => $side,
                'job_type' => $this->jobType(),
            ],
            [
                'status' => $status,
                's3_key' => $s3Key,
                'error_message' => $errorMessage,
                'duration_ms' => $durationMs,
                'model' => $model,
                'voice' => $voice,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * @param $question
     * @param $last_revision
     * @param $current_revision_id
     * @return array
     * @throws FileNotFoundException
     */
    private function _s3KeysOfPreviouslyGeneratedAudio($question, $last_revision, $current_revision_id): array
    {
        $s3_keys = [];
        $question_qti_json = json_decode($question->qti_json, 1);
       //Log::info($question_qti_json);
        //Log::info($last_revision->revision_number);
        $last_revision_qti_json = json_decode($last_revision->qti_json, 1);
        //Log::info($last_revision_qti_json);
        //Log::info($question->id . ' ' . $last_revision->id);
        $question_card = $question_qti_json['card'];
        $last_revision_card = $last_revision_qti_json['card'];

        foreach (['front', 'back'] as $side) {
            //Log::info($side);
            $word_to_match = $side === 'front' ? 'term' : 'answer';
            if ($question_card[$word_to_match] === $last_revision_card[$word_to_match]
                && str_contains($question_card["{$side}Type"], 'text')
                && $question_card["{$side}TTSLanguage"] === $last_revision_card["{$side}TTSLanguage"]) {
                $s3_key = $last_revision->revision_number === 0
                    ? "uploads/flashcard-tts/$question->id/0/$side.mp3"
                    : "uploads/flashcard-tts/$question->id/$last_revision->id/$side.mp3";
                //Log::info("s3 key: " . $s3_key);
                if (Storage::disk('s3')->exists($s3_key)) {
                    $new_s3_key = "uploads/flashcard-tts/$question->id/$current_revision_id/$side.mp3";
                    //Log::info("new key". $new_s3_key);
                    $s3_keys[$side] = $new_s3_key;
                    $contents = Storage::disk('s3')->get($s3_key);
                    Storage::disk('s3')->put($new_s3_key, $contents);
                    //Log::info("New file exists: " . (Storage::disk('s3')->exists($new_s3_key) ? 'true' : 'false'));
                }
            }
        }
        return $s3_keys;
    }
}
