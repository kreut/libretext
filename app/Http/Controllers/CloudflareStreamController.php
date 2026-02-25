<?php

namespace App\Http\Controllers;

use App\CloudflareStreamVideo;
use App\Exceptions\Handler;
use App\Jobs\UploadVideoToCloudflareStream;
use App\Services\CloudflareStream;
use Exception;
use Illuminate\Http\Request;

class CloudflareStreamController extends Controller
{

    public function status(Request $request): array
    {
        $response['type'] = 'error';
        try {
            $s3Path = $request->input('s3_path');

            if (!$s3Path) {
                $response['message'] = 'No S3 path provided.';
                return $response;
            }

            $cloudflareStreamVideo = CloudflareStreamVideo::where('s3_path', $s3Path)->first();

            if (!$cloudflareStreamVideo) {
                $response['status'] = 'not_started';
                $response['type'] = 'success';
                return $response;
            }

            $response['status'] = $cloudflareStreamVideo->status;
            $response['cloudflare_uid'] = $cloudflareStreamVideo->cloudflare_uid;
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting the Cloudflare Stream video status.";
        }
        return $response;
    }

    public function upload(Request $request): array
    {
        $response['type'] = 'error';
        try {
            $s3Path = $request->input('s3_path');

            if (!$s3Path) {
                $response['message'] = 'No S3 path provided.';
                return $response;
            }

            $existing = CloudflareStreamVideo::where('s3_path', $s3Path)->first();

            if ($existing && in_array($existing->status, ['processing', 'ready'])) {
                $response['message'] = "This video is already {$existing->status}.";
                $response['type'] = 'info';
                return $response;
            }
dd('cannot upload unless local');
            CloudflareStreamVideo::updateOrCreate(
                ['s3_path' => $s3Path],
                ['status' => 'pending', 'error_message' => null]
            );

            UploadVideoToCloudflareStream::dispatch($s3Path);

            $response['type'] = 'success';
            $response['message'] = 'Video upload to Cloudflare Stream has been queued.';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error queuing the video upload.";
        }
        return $response;
    }
}
