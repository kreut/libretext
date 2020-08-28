<?php


namespace App\Traits;
use Illuminate\Support\Facades\Storage;

trait S3
{
    public function getTemporaryUrl($assignment_id, $file)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(5));
    }

}
