<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

            $sides[$side] = $text;
        }

        return $sides;
    }

    /**
     * Call the ElevenLabs TTS API for one side and store the mp3 on S3.
     * @throws Exception
     */
    public function processForSide(string $side, $payload, string $language = ''): ?string
    {
        $model = 'eleven_flash_v2_5';
        switch ($language) {
            case('English'):
                $voiceId = 'F7hCTbeEDbm7osolS21j';
                $prompt = 'Speak clearly and naturally, as if reading a single vocabulary word or short phrase for a language learning flashcard. No filler sounds, no hesitation. Deliver it cleanly and confidently.';
                break;
            case('Spanish'):
                $voiceId = 'wHiOjOiwglSlcqGt7GVl';
                $prompt = 'Habla con claridad y naturalidad, como si leyeras una palabra o frase corta para una tarjeta de vocabulario de aprendizaje de idiomas. Sin sonidos de relleno, sin dudas. Pronúnciala de forma limpia y segura.';
                break;
            case('French'):
                $voiceId = 'MNiuKciqE420DCRJtdeb';
                $prompt = "Parle clairement et naturellement, comme si tu lisais un mot de vocabulaire ou une courte phrase pour une carte d'apprentissage de langue. Pas de sons de remplissage, pas d'hésitation. Prononce-le de façon nette et assurée.";
                break;
            default:
                throw new Exception("No voiceId exists for the language: $language");
        }
        $s3Key = "uploads/flashcard-tts/{$this->questionId}/{$this->questionRevisionId}/{$side}.mp3";

        $this->upsertLog($side, 'processing', null, null, null, $model, $voiceId);

        $startMs = (int)round(microtime(true) * 1000);
        $api_key = DB::table('key_secrets')->where('key', 'elevenlabs')->first()->secret;
        try {
            $httpResponse = Http::withHeaders([
                'xi-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'audio/mpeg',
            ])
                ->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                    'text' => $this->preprocessTtsText($payload),
                    'model_id' => 'eleven_v3',
                    'voice_settings' => [
                        'stability' => 0.65,
                        'similarity_boost' => 0.75,
                        'style' => 0.0,
                        'use_speaker_boost' => true,
                        'prompt' => $prompt,
                    ],
                ]);

            if (!$httpResponse->successful()) {
                $json = $httpResponse->json();
                $error = is_array($json) && isset($json['detail']['message'])
                    ? $json['detail']['message']
                    : $httpResponse->body();
                throw new Exception("ElevenLabs TTS error: {$error}");
            }

            $response = $httpResponse->body();

            if (!$response) {
                throw new Exception("Empty response from ElevenLabs TTS for question {$this->questionId} side={$side}");
            }

            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            Storage::disk('s3')->put($s3Key, $response);

            $this->upsertLog($side, 'success', $s3Key, null, $durationMs, $model, $voiceId);

            return $s3Key;

        } catch (Exception $e) {
            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            $h = new Handler(app());
            $h->report($e);

            $this->upsertLog($side, 'error', null, $e->getMessage(), $durationMs, $model, $voiceId);

            return null;
        }
    }

    private function preprocessTtsText(string $text): string
    {
        if (str_word_count($text) <= 5) {
            $text = '<break time="1.5s" /> ' . $text;
        }
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
        if (!preg_match('/[.!?;,]$/', $text)) {
            $text .= '.';
        }

        return $text;
    }

}
