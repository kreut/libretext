<?php

namespace App\Jobs;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Orhanerday\OpenAi\OpenAi;

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
            $mediaS3Key = trim($card["{$side}MediaS3Key"] ?? '');
            $caption_language = $card["{$side}CaptionLanguage"] ?? false;
            if (!$caption_language || $mediaS3Key === '') {
                continue;
            }

            $sides[$side] = [
                's3Key' => $mediaS3Key,
                'language' => $card["{$side}CaptionLanguage"] ?? null,
            ];
        }

        return $sides;
    }

    /**
     * Download audio from S3, send to OpenAI Whisper, store returned VTT on S3.
     *
     * @param string $side 'front' or 'back'
     * @param array $payload ['s3Key' => '...', 'language' => 'es'|null]
     * @throws Exception
     */
    protected function processForSide(string $side, $payload, $language = ''): ?string
    {
        $audioS3Key = $payload['s3Key'];
        $language = $payload['language'] ?? null;

        $model = 'whisper-1';
        $vttS3Key = "uploads/flashcard-vtt/{$this->questionId}/{$this->questionRevisionId}/{$side}.vtt";

        $this->upsertLog($side, 'processing', null, null, null, $model);

        $startMs = (int)round(microtime(true) * 1000);

        $efs_dir = "/mnt/local/";
        $is_efs = is_dir($efs_dir);
        $storage_path = $is_efs
            ? $efs_dir
            : Storage::disk('local')->getAdapter()->getPathPrefix();

        $full_dir = rtrim($storage_path, '/') . '/whisper-files';
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0755, true);
        }

        $local_filename = $this->questionId . '_' . $this->questionRevisionId . '_' . $side . '_' . basename($audioS3Key);
        $full_path = $full_dir . '/' . $local_filename;


        try {
            if (!Storage::disk('s3')->exists($audioS3Key)) {
                throw new Exception("Audio file not found on S3: {$audioS3Key}");
            }

            $cFile = curl_file_create($full_path);

            $whisperParams = [
                'model' => $model,
                'file' => $cFile,
                'response_format' => 'vtt',
            ];

            if ($language) {
                $whisperParams['language'] = $language;
            }

            $openai = new OpenAi(config('myconfig.openai_api_key'));
            $response = $openai->transcribe($whisperParams);

            if (file_exists($full_path)) {
                unlink($full_path);
            }

            $json_response = json_decode($response);
            if ($json_response && isset($json_response->error)) {
                throw new Exception("OpenAI Whisper error: {$json_response->error->message}");
            }

            $vttContents = $response;

            if (!$vttContents) {
                throw new Exception("Empty VTT response from OpenAI Whisper for question {$this->questionId} side={$side}");
            }

            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            Storage::disk('s3')->put($vttS3Key, $vttContents);

            $this->upsertLog($side, 'success', $vttS3Key, null, $durationMs, $model);

            return $vttS3Key;

        } catch (Exception $e) {
            $durationMs = (int)round(microtime(true) * 1000) - $startMs;

            if (file_exists($full_path)) {
                unlink($full_path);
            }

            $h = new Handler(app());
            $h->report($e);

            $this->upsertLog($side, 'error', null, $e->getMessage(), $durationMs, $model);

            return null;
        }
    }
}
