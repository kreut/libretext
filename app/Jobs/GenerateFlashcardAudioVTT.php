<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateFlashcardAudioVTT extends FlashcardAudioJob
{
    protected function jobType(): string
    {
        return 'vtt';
    }

    protected function s3JsonKey(string $side): string
    {
        return "{$side}MediaVttS3Key";
    }

    /**
     * Determine which sides have uploaded audio media that need VTT generated.
     * Returns ['front' => ['s3Key' => '...', 'language' => 'es'], ...] for eligible sides only.
     *
     * Only sides with mediaType === 'audio' and a non-empty mediaS3Key are eligible.
     * Always re-generates — no skip if VTT already exists, so re-uploaded audio
     * gets a fresh transcript.
     */
    protected function getSidesToProcess(array $card): array
    {
        $sides = [];

        foreach (['front', 'back'] as $side) {
            $mediaType  = $card["{$side}MediaType"] ?? '';
            $mediaS3Key = trim($card["{$side}MediaS3Key"] ?? '');

            if ($mediaType !== 'audio' || $mediaS3Key === '') {
                continue;
            }

            $sides[$side] = [
                's3Key'    => $mediaS3Key,
                'language' => $card["{$side}CaptionLanguage"] ?? null,
            ];
        }

        return $sides;
    }

    /**
     * Download audio from S3, send to OpenAI Whisper, store returned VTT on S3.
     *
     * @param string $side    'front' or 'back'
     * @param array  $payload ['s3Key' => '...', 'language' => 'es'|null]
     */
    protected function processForSide(string $side, $payload): ?string
    {
        $audioS3Key = $payload['s3Key'];
        $language   = $payload['language'] ?? null;

        $model    = 'whisper-1';
        $vttS3Key = "uploads/flashcard-vtt/{$this->questionId}/{$this->questionRevisionId}/{$side}.vtt";

        $this->upsertLog($side, 'processing', null, null, null, $model);

        $startMs = (int)round(microtime(true) * 1000);

        // Use /tmp on Vapor/Lambda and Linux/Mac; fall back to sys_get_temp_dir() on Windows
        $tmpDir  = (is_dir('/tmp') && is_writable('/tmp')) ? '/tmp' : sys_get_temp_dir();
        $tmpPath = $tmpDir . '/fc_audio_' . $this->questionId . '_' . $this->questionRevisionId . '_' . $side . '.mp3';

        try {
            if (!Storage::disk('s3')->exists($audioS3Key)) {
                throw new Exception("Audio file not found on S3: {$audioS3Key}");
            }

            file_put_contents($tmpPath, Storage::disk('s3')->get($audioS3Key));

            $whisperParams = [
                'model'           => $model,
                'response_format' => 'vtt',
            ];

            // Passing the language improves Whisper accuracy and avoids
            // language auto-detection overhead
            if ($language) {
                $whisperParams['language'] = $language;
            }

            try {
                $httpResponse = Http::withToken(config('myconfig.openai_api_key'))
                    ->attach('file', file_get_contents($tmpPath), basename($tmpPath))
                    ->post('https://api.openai.com/v1/audio/transcriptions', $whisperParams);
            } finally {
                if (file_exists($tmpPath)) {
                    unlink($tmpPath);
                }
            }

            if (!$httpResponse->successful()) {
                $json  = $httpResponse->json();
                $error = is_array($json) && isset($json['error']['message'])
                    ? $json['error']['message']
                    : $httpResponse->body();
                throw new Exception("OpenAI Whisper error: {$error}");
            }

            $vttContents = $httpResponse->body();

            if (!$vttContents) {
                throw new Exception("Empty VTT response from OpenAI Whisper for question {$this->questionId} side={$side}");
            }

            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            Storage::disk('s3')->put($vttS3Key, $vttContents);

            $this->upsertLog($side, 'success', $vttS3Key, null, $durationMs, $model);

            return $vttS3Key;

        } catch (Exception $e) {
            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }

            $h = new Handler(app());
            $h->report($e);

            $this->upsertLog($side, 'error', null, $e->getMessage(), $durationMs, $model);

            return null;
        }
    }
}
