<?php

namespace App\Console\Commands\CloudStream;

use App\CloudflareStreamVideo;
use App\Jobs\UploadVideoToCloudflareStream;
use Illuminate\Console\Command;

class RetryFailedCloudflareUploads extends Command
{
    protected $signature = 'cloudflare:retry-failed';
    protected $description = 'Retry all Cloudflare Stream uploads that have an error';

    public function handle()
    {
        $failed = CloudflareStreamVideo::whereNotNull('error_message')->get();

        if ($failed->isEmpty()) {
            $this->info('No failed uploads found.');
            return;
        }

        $this->info("Found {$failed->count()} failed upload(s). Retrying...");

        foreach ($failed as $video) {
            $this->line("  Queuing: {$video->s3_path}");
            $this->line("    Previous error: {$video->error_message}");

            $video->update([
                'status' => 'pending',
                'error_message' => null,
                'cloudflare_uid' => null,
            ]);

            UploadVideoToCloudflareStream::dispatch($video->s3_path);
        }

        $this->info("Done. {$failed->count()} upload(s) queued.");
    }
}
