<?php


namespace App\Traits;


use Exception;
use Illuminate\Support\Facades\Storage;

trait S3
{
    public function getS3($object){
        try {
            Storage::disk('s3')->get($object);
        } catch (Exception $e){
            Storage::disk('backup_s3')->get($object);
        }
    }
    public function getTemporaryUrl($assignment_id, $file)
    {
        return Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(360));
    }

    public function fileValidator()
    {
        return ['required', 'mimes:pdf,txt,png,jpeg,jpg', 'max:500000'];//update in UploadFiles.js
    }

    public function audioFileValidator()
    {
        return ['required', 'mimes:mpga,mp3', 'max:500000'];//update in UploadFiles.js
    }

    public function bytesToHuman($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GiB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }


}
