<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateFlashcardTTS extends FlashcardAudioJob
{
    protected function jobType(): string
    {
        return 'tts';
    }

    protected function s3JsonKey(string $side): string
    {
        return "{$side}TtsS3Key";
    }

    /**
     * Determine which sides need TTS generated.
     * Returns ['front' => 'text...', 'back' => 'text...'] for eligible sides only.
     *
     * Eligible types: 'text_only' and 'text_media' (text part only).
     * 'free_form' (rich HTML) and 'media' (media-only) are skipped.
     */
    protected function getSidesToProcess(array $card): array
    {
        $sides = [];
        $textFields = ['front' => 'term', 'back' => 'answer'];

        foreach (['front', 'back'] as $side) {
            $type = $card["{$side}Type"] ?? '';

            if (!in_array($type, ['text', 'text_only', 'text_media'])) {
                continue;
            }

            $text = trim($card[$textFields[$side]] ?? '');

            if ($text === '') {
                continue;
            }

            // For text_media with a non-decorative image, append the alt text
            if ($type === 'text_media'
                && ($card["{$side}MediaType"] ?? '') === 'image'
                && empty($card["{$side}MediaDecorative"])
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
     * Call the OpenAI TTS API for one side and store the mp3 on S3.
     */
    protected function processForSide(string $side, $text): ?string
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
                    'model'           => $model,
                    'input'           => $this->preprocessTtsText($text),
                    'voice'           => $voice,
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
