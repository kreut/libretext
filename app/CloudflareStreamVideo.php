<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CloudflareStreamVideo extends Model
{
    protected $fillable = [
        's3_path',
        'cloudflare_uid',
        'status',
        'error_message',
    ];

    /**
     * @param string $s3Path
     * @return string|null Returns the Cloudflare UID if ready, null otherwise
     */
    public static function getCloudflareUid(string $s3Path): ?string
    {
        $record = self::where('s3_path', $s3Path)
            ->where('status', 'ready')
            ->first();

        return $record ? $record->cloudflare_uid : null;
    }

    /**
     * @param string $s3Path
     * @return bool
     */
    public static function isPendingOrProcessing(string $s3Path): bool
    {
        return self::where('s3_path', $s3Path)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();
    }
}
