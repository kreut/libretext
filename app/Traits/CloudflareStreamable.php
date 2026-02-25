<?php

namespace App\Traits;

use App\CloudflareStreamVideo;
use App\Helpers\Helper;
use App\Jobs\UploadVideoToCloudflareStream;
use App\Services\CloudflareStream;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait CloudflareStreamable
{
    /**
     * @param string $s3Path
     * @param string $vttUrl
     * @return string|null
     * @throws FileNotFoundException
     */
    public function getCloudflareStreamUid(string $s3Path, string $vttUrl = ''): ?string
    {
        if (
            (Cache::get('cloudflare_stream_disabled') && !Helper::isAdmin()) ||
            (Cache::get('cloudflare_stream_disabled_admin') && Helper::isAdmin())
        ) {
            return null;
        }

        $record = CloudflareStreamVideo::where('s3_path', $s3Path)->first();

        if ($record && $record->cloudflare_uid && $record->status === 'ready') {

            // Increment serve count (rate limited)
            $cacheKey = "cf_served:{$s3Path}:" . request()->ip();
            if (!Cache::has($cacheKey)) {
                $record->increment('times_served');
                Cache::put($cacheKey, true, 5);
            }
            if ($vttUrl && Storage::disk('s3')->exists($vttUrl)) {

                $vttContent = Storage::disk('s3')->get($vttUrl);
                $hash = md5($vttContent);

                if ($record->captions_hash !== $hash) {
                    $cloudflareStream = new CloudflareStream();
                    $result = $cloudflareStream->uploadCaptions(
                        $record->cloudflare_uid,
                        $vttContent
                    );

                    if ($result) {
                        $record->captions_hash = $hash;
                        $record->save();
                    }
                }
            }

            // Return signed token
            $cloudflareStream = new CloudflareStream();
            return $cloudflareStream->getSignedToken($record->cloudflare_uid)
                ?? $record->cloudflare_uid;
        }
        if (!$record || $record->status === 'error') {

            CloudflareStreamVideo::updateOrCreate(
                ['s3_path' => $s3Path],
                [
                    'status' => 'pending',
                    'error_message' => null,
                ]
            );

            UploadVideoToCloudflareStream::dispatch($s3Path);
        }
        return null;
    }
}
