<?php

namespace App\Services;

use App\Exceptions\Handler;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareStream
{
    private $accountId;
    private $apiToken;
    private $baseUrl;
    /**
     * @var mixed|string
     */
    private $keyId;

    public function __construct()
    {
        $this->accountId = '';
        $this->apiToken = '';
        $this->baseUrl = '';
        $this->keyId = '';
        $cloudflare_stream_credentials = DB::table('cloudflare_stream_credentials')->first();
        if ($cloudflare_stream_credentials) {
            $this->accountId = $cloudflare_stream_credentials->account_id;
            $this->apiToken = $cloudflare_stream_credentials->api_token;
            $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/stream";
            $this->keyId = $cloudflare_stream_credentials->signing_key_id;
        }
    }

    /**
     * @param string $s3TemporaryUrl
     * @param string $s3Path
     * @return array|mixed|null
     * @throws Exception
     */
    public function uploadFromUrl(string $s3TemporaryUrl, string $s3Path)
    {
        $response['type'] = 'error';
        try {
            $result = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/copy", [
                    'url' => $s3TemporaryUrl,
                    'meta' => ['s3_path' => $s3Path,
                        'source' => 'adapt',
                        'environment' => app()->environment()],
                    'requireSignedURLs' => true,
                ]);
            if ($result->successful()) {
                $response = $result->json();
                $response['type'] = 'success';
            } else {
                throw new Exception("Cloudflare Stream upload failed for {$s3Path}: " . $result->body());
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['type'] = 'error';
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param string $cloudflareUid
     * @return array|mixed
     * @throws Exception
     */
    public
    function getVideoStatus(string $cloudflareUid)
    {
        try {
            $response['type'] = 'error';
            $result = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/{$cloudflareUid}");

            if ($result->successful()) {
                $response = $result->json();
                $response['type'] = 'success';
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * @param string $cloudflareUid
     * @return array
     */
    public
    function deleteVideo(string $cloudflareUid): array
    {
        $response['type'] = 'error';
        $result = Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/$cloudflareUid");
        if ($result->successful()) {
            DB::table('cloudflare_stream_videos')->where('cloudflare_uid', $cloudflareUid)->delete();
            $response['type'] = 'success';
        } else {
            $response['message'] = $result->json();
        }
        return $response;
    }

    /**
     * @throws Exception
     */
    public
    function getSignedToken(string $cloudflareUid, int $expiresInSeconds = 3600): ?string
    {
        $keyId = $this->keyId;
        $pem = DB::table('key_secrets')
            ->where('key', 'cloudflare_stream_signing_key_pem')
            ->value('secret');

        if (!$keyId || !$pem) {
            $h = new Handler(app());
            $h->report(new Exception("Cloudflare Stream signing not configured: " .
                (!$keyId ? "missing key ID" : "missing PEM key")));
            return null;
        }

        $header = json_encode(['alg' => 'RS256', 'kid' => $keyId]);
        $payload = json_encode([
            'sub' => $cloudflareUid,
            'kid' => $keyId,
            'exp' => time() + $expiresInSeconds,
        ]);

        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

        $signature = '';
        openssl_sign("$base64Header.$base64Payload", $signature, $pem, OPENSSL_ALGO_SHA256);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "$base64Header.$base64Payload.$base64Signature";
    }

    public
    function uploadCaptions(string $cloudflareUid, string $vttContent, string $language = 'captions'): bool
    {
        $result = Http::withToken($this->apiToken)
            ->attach('file', $vttContent, 'captions.vtt')
            ->put("{$this->baseUrl}/{$cloudflareUid}/captions/{$language}");

        if (!$result->successful()) {
            $h = new Handler(app());
            $h->report(new Exception("Cloudflare captions upload failed for {$cloudflareUid}: " . $result->body()));
        }

        return $result->successful();
    }
}
