<?php

namespace App\Jobs;

use App\CloudflareStreamVideo;
use App\Exceptions\Handler;
use App\Services\CloudflareStream;
use App\Traits\S3;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadVideoToCloudflareStream implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, S3;

    public $tries = 3;
    public $backoff = 60;

    protected $s3Path;

    /**
     * @param string $s3Path
     */
    public function __construct(string $s3Path)
    {
        $this->s3Path = $s3Path;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handle()
    {
        try {
            $cloudflareStreamVideo = CloudflareStreamVideo::where('s3_path', $this->s3Path)->first();

            if (!$cloudflareStreamVideo) {
                throw new Exception("CloudflareStreamVideo record not found for s3_path: {$this->s3Path}");

            }

            if ($cloudflareStreamVideo->status === 'ready') {
                return;
            }

            $cloudflareStreamVideo->update(['status' => 'processing']);

            // Generate a temporary S3 URL for Cloudflare to fetch
            Log::info($this->s3Path);
            $temporaryUrl = Storage::disk('s3')->temporaryUrl($this->s3Path, now()->addHours(6));

            $cloudflareStream = new CloudflareStream();
            $result = $cloudflareStream->uploadFromUrl($temporaryUrl, $this->s3Path);

            if ($result['type'] === 'error') {
                $cloudflareStreamVideo->update([
                    'status' => 'error',
                    'error_message' => $result['message'],
                ]);
                throw new Exception("Cloudflare Stream upload failed for {$this->s3Path}: {$result['message']}");

            }
            $cloudflareStreamVideo->update([
                'cloudflare_uid' => $result['result']['uid'],
                'status' => 'processing',
            ]);

            // Dispatch a job to poll for readiness
            PollCloudflareStreamVideoStatus::dispatch($cloudflareStreamVideo->id)
                ->delay(now()->addSeconds(30));

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);

            if (isset($cloudflareStreamVideo)) {
                $cloudflareStreamVideo->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }
}
