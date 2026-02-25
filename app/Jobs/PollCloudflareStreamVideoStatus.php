<?php

namespace App\Jobs;

use App\CloudflareStreamVideo;
use App\Exceptions\Handler;
use App\Services\CloudflareStream;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollCloudflareStreamVideoStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 20;
    public $backoff = 30;

    protected $cloudflareStreamVideoId;

    /**
     * @param int $cloudflareStreamVideoId
     */
    public function __construct(int $cloudflareStreamVideoId)
    {
        $this->cloudflareStreamVideoId = $cloudflareStreamVideoId;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        try {
            $cloudflareStreamVideo = CloudflareStreamVideo::find($this->cloudflareStreamVideoId);

            if (!$cloudflareStreamVideo || !$cloudflareStreamVideo->cloudflare_uid) {
                Log::warning("CloudflareStreamVideo {$this->cloudflareStreamVideoId} not found or missing UID.");
                return;
            }

            if ($cloudflareStreamVideo->status === 'ready') {
                return;
            }

            $cloudflareStream = new CloudflareStream();
            $cloudflare_result = $cloudflareStream->getVideoStatus($cloudflareStreamVideo->cloudflare_uid);
            if ($cloudflare_result['type'] === 'error') {
                Log::warning("Could not poll Cloudflare status for UID {$cloudflareStreamVideo->cloudflare_uid}: {$cloudflare_result['message']}");
                // Re-dispatch to try again
                self::dispatch($this->cloudflareStreamVideoId)
                    ->delay(now()->addSeconds(30));
                return;
            }
            $result = $cloudflare_result['result'];
            if ($result['status']['state'] === 'ready') {
                $cloudflareStreamVideo->update(['status' => 'ready']);
            } elseif ($result['status']['state'] === 'error') {
                $errorMessage = $result['status']['errorReasonText'] ?? 'Unknown Cloudflare processing error';
                $cloudflareStreamVideo->update([
                    'status' => 'error',
                    'error_message' => $errorMessage,
                ]);
                throw new Exception("Cloudflare Stream processing error for {$cloudflareStreamVideo->s3_path}: {$errorMessage}");
            } else {
                // Still processing — re-dispatch
                self::dispatch($this->cloudflareStreamVideoId)
                    ->delay(now()->addSeconds(30));
            }

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
