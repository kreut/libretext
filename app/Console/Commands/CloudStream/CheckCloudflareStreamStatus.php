<?php

namespace App\Console\Commands\CloudStream;

use App\CloudflareStreamVideo;
use App\Services\CloudflareStream;
use Illuminate\Console\Command;

class CheckCloudflareStreamStatus extends Command
{
    protected $signature = 'cloudflare:check-status {cloudflare_uid}';
    protected $description = 'Check the processing status of a Cloudflare Stream video';

    public function handle(): int
    {
        $cloudflareUid = $this->argument('cloudflare_uid');

        $cloudflareStream = new CloudflareStream();
        $result = $cloudflareStream->getVideoStatus($cloudflareUid);

        if ($result['type'] === 'error') {
            $this->error("Error: {$result['message']}");
            return 1;
        }

        $this->info("UID: {$cloudflareUid}");
        $this->info("State: {$result['state']}");

        if ($result['state'] === 'ready') {
            $this->info("Embed URL: https://iframe.videodelivery.net/{$cloudflareUid}");

            // Update DB if we have a record
            CloudflareStreamVideo::where('cloudflare_uid', $cloudflareUid)
                ->update(['status' => 'ready']);
        }

        if (isset($result['data']['duration'])) {
            $this->info("Duration: {$result['data']['duration']} seconds");
        }

        return 0;
    }
}
