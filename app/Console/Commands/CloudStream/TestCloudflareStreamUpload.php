<?php

namespace App\Console\Commands\CloudStream;

use App\CloudflareStreamVideo;
use App\Services\CloudflareStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestCloudflareStreamUpload extends Command
{
    protected $signature = 'cloudflare:test-upload {s3_path}';
    protected $description = 'Test uploading an S3 video to Cloudflare Stream and poll until ready';

    //uploads/question-media/001eccea6029073fe29ef209e4b37c6f.mp4
    public function handle(): int
    {
        $s3Path = $this->argument('s3_path');

        $this->info("Testing Cloudflare Stream upload for: {$s3Path}");

        // Step 1: Verify S3 file exists
        if (!Storage::disk('s3')->exists($s3Path)) {
            $this->error("File not found on S3: {$s3Path}");
            return 1;
        }
        $this->info('✓ File exists on S3');

        // Step 2: Generate temporary URL
        $temporaryUrl = Storage::disk('s3')->temporaryUrl($s3Path, now()->addHours(6));
        $this->info('✓ Generated temporary S3 URL');

        // Step 3: Upload to Cloudflare
        $cloudflareStream = new CloudflareStream();
        $this->info('Uploading to Cloudflare Stream...');

        $result = $cloudflareStream->uploadFromUrl($temporaryUrl, $s3Path);

        if ($result['type'] === 'error') {
            $this->error("Upload failed: {$result['message']}");
            return 1;
        }

        $cloudflareUid = $result['cloudflare_uid'];
        $this->info("✓ Upload initiated. Cloudflare UID: {$cloudflareUid}");

        // Step 4: Save to database
        CloudflareStreamVideo::updateOrCreate(
            ['s3_path' => $s3Path],
            [
                'cloudflare_uid' => $cloudflareUid,
                'status' => 'processing',
                'error_message' => null,
            ]
        );
        $this->info('✓ Saved to cloudflare_stream_videos table');

        // Step 5: Poll for readiness
        $this->info('Polling for video readiness (this may take a few minutes)...');
        $maxAttempts = 40;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;
            sleep(15);

            $statusResult = $cloudflareStream->getVideoStatus($cloudflareUid);

            if ($statusResult['type'] === 'error') {
                $this->warn("  Attempt {$attempt}: Could not get status - {$statusResult['message']}");
                continue;
            }

            $state = $statusResult['state'];
            $this->info("  Attempt {$attempt}: Status = {$state}");

            if ($state === 'ready') {
                CloudflareStreamVideo::where('s3_path', $s3Path)->update(['status' => 'ready']);
                $this->info('');
                $this->info('✓ Video is ready!');
                $this->info("  Cloudflare UID: {$cloudflareUid}");
                $this->info("  Embed URL: https://iframe.videodelivery.net/{$cloudflareUid}");
                $this->info("  Stream element: <stream src=\"{$cloudflareUid}\" controls></stream>");
                return 0;
            }

            if ($state === 'error') {
                $errorMsg = $statusResult['data']['status']['errorReasonText'] ?? 'Unknown error';
                CloudflareStreamVideo::where('s3_path', $s3Path)->update([
                    'status' => 'error',
                    'error_message' => $errorMsg,
                ]);
                $this->error("Video processing failed: {$errorMsg}");
                return 1;
            }
        }

        $this->warn("Timed out after {$maxAttempts} attempts. Video may still be processing.");
        $this->info("Check status later with: php artisan cloudflare:check-status {$cloudflareUid}");
        return 1;
    }
}
