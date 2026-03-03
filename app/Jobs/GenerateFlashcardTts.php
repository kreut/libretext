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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class GenerateFlashcardTts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $questionId;

    /**
     * @var int  0 means no revision row exists yet (brand-new question)
     */
    private $questionRevisionId;

    /**
     * Create a new job instance.
     */
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

        // Use the revision's qti_json if a revision exists, otherwise fall back
        // to the question's own qti_json (brand-new question, revision_id = 0).
        if ($this->questionRevisionId) {
            $revision = QuestionRevision::find($this->questionRevisionId);
            $qtiJson = $revision ? $revision->qti_json : $question->qti_json;
        } else {
            $revision = null;
            $qtiJson = $question->qti_json;
        }

        $qti = json_decode($qtiJson, true);
        $card = $qti['card'] ?? [];

        $sides = $this->getSidesToGenerate($card);
        Log::info(json_encode($sides));
        if (empty($sides)) {
            return;
        }

        foreach ($sides as $side => $text) {
            $s3Key = $this->generateForSide($side, $text);
            if ($s3Key) {
                $this->writeS3KeyToQtiJson($question, $revision, $side, $s3Key);
            }
        }
    }

    /**
     * Determine which sides need TTS generated.
     * Returns ['front' => 'text...', 'back' => 'text...'] for eligible sides only.
     *
     * Eligible types: 'text' and 'text_media' (text part only).
     * 'free_form' (rich HTML) and 'media' (media-only) are skipped.
     *
     * @param array $card
     * @return array
     */
    private function getSidesToGenerate(array $card): array
    {
        $sides = [];

        // Both text_only and text_media store text in 'term' (front) and 'answer' (back).
        $textFields = ['front' => 'term', 'back' => 'answer'];

        foreach (['front', 'back'] as $side) {
            $type = $card["{$side}Type"] ?? '';

            if (!in_array($type, ['text', 'text_only', 'text_media'])) {
                continue; // free_form, media, or unknown — skip
            }

            $text = trim($card[$textFields[$side]] ?? '');

            if ($text === '') {
                continue;
            }

            // For text_media with an image, append the alt text as an image description
            if ($type === 'text_media'
                && ($card["{$side}MediaType"] ?? '') === 'image'
                && !empty($card["{$side}MediaDecorative"]) === false  // not purely decorative
                && !empty($card["{$side}MediaAlt"])
            ) {
                $altText = trim($card["{$side}MediaAlt"]);
                if ($altText !== '') {
                    $text = "Text: {$text}. Image description: {$altText}";
                }
            }

            $sides[$side] = $text;
        }

        return $sides;
    }

    /**
     * Call the OpenAI TTS API for one side, store the mp3 on S3, and log the outcome.
     * Returns the S3 key on success, or null on failure.
     *
     * @param string $side 'front' or 'back'
     * @param string $text
     * @return string|null
     */
    private function generateForSide(string $side, string $text): ?string
    {
        $model = 'tts-1';
        $voice = 'nova';
        $s3Key = "uploads/flashcard-tts/{$this->questionId}/{$this->questionRevisionId}/{$side}.mp3";

        $this->upsertLog($side, 'processing', null, null, null, $model, $voice);

        $startMs = (int)round(microtime(true) * 1000);

        try {
            $httpResponse = Http::withToken(config('myconfig.openai_api_key'))
                ->withHeaders(['Accept' => 'audio/mpeg'])
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => $model,
                    'input' => $this->preprocessTtsText($text),
                    'voice' => $voice,
                    'response_format' => 'mp3',
                ]);

            if (!$httpResponse->successful()) {
                $json = $httpResponse->json();
                $error = is_array($json) && isset($json['error']['message'])
                    ? $json['error']['message']
                    : $httpResponse->body();
                throw new Exception("OpenAI TTS error: {$error}");
            }

            $response = $httpResponse->body();

            if (!$response) {
                throw new Exception("Empty response from OpenAI TTS for question {$this->questionId} side={$side}");
            }

            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            Storage::disk('s3')->put($s3Key, $response);

            $this->upsertLog($side, 'success', $s3Key, null, $durationMs, $model, $voice);

            return $s3Key;

        } catch (Exception $e) {
            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            $h = new Handler(app());
            $h->report($e);

            $this->upsertLog($side, 'error', null, $e->getMessage(), $durationMs, $model, $voice);

            return null;
        }
    }

    /**
     * Write the generated S3 key back into qti_json on both the questions row
     * and the question_revisions row (if one exists), so formatQtiJson can
     * generate a temporary URL from it at read time — exactly as it does for
     * frontMediaS3Key / backMediaS3Key.
     *
     * @param Question $question
     * @param QuestionRevision|null $revision
     * @param string $side 'front' or 'back'
     * @param string $s3Key
     */
    private function writeS3KeyToQtiJson(Question $question, ?QuestionRevision $revision, string $side, string $s3Key): void
    {
        $jsonKey = "{$side}TtsS3Key";

        // Update the questions row
        $qti = json_decode($question->qti_json, true);
        $qti['card'][$jsonKey] = $s3Key;
        $question->qti_json = json_encode($qti);
        $question->save();

        // Update the revision row if one exists
        if ($revision) {
            $revQti = json_decode($revision->qti_json, true);
            $revQti['card'][$jsonKey] = $s3Key;
            $revision->qti_json = json_encode($revQti);
            $revision->save();
        }
    }

    /**
     * Upsert a row in flashcard_tts_logs for (question_id, question_revision_id, side).
     */
    private function upsertLog(
        string  $side,
        string  $status,
        ?string $s3Key,
        ?string $errorMessage,
        ?int    $durationMs,
        string  $model,
        string  $voice
    ): void
    {
        DB::table('flashcard_tts_logs')->updateOrInsert(
        // Match on unique key
            [
                'question_id' => $this->questionId,
                'question_revision_id' => $this->questionRevisionId,
                'side' => $side,
            ],
            // Values to set on insert or update
            [
                'status' => $status,
                's3_key' => $s3Key,
                'error_message' => $errorMessage,
                'duration_ms' => $durationMs,
                'tts_model' => $model,
                'tts_voice' => $voice,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function preprocessTtsText(string $text): string
    {
        // Replace gender suffix patterns like aburrido/a → aburrido, aburrida
        $text = preg_replace_callback(
            '/(\w+)\/a\b/',
            function ($matches) {
                $masculine = $matches[1];
                if (substr($masculine, -1) === 'o') {
                    $feminine = substr($masculine, 0, -1) . 'a';
                    return "{$masculine}, {$feminine}";
                }
                return $masculine;
            },
            $text
        );

        // Replace other slash constructs like él/ella → él, ella
        $text = preg_replace('/(\w+)\/(\w+)/', '$1, $2', $text);

        $text = trim($text);

        // Add a period if the text doesn't already end with punctuation
        // This helps OpenAI TTS pronounce short words clearly
        if (!preg_match('/[.!?;,]$/', $text)) {
            $text .= '.';
        }

        return $text;
    }
}
