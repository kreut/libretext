<?php


namespace App\Traits;

use App\SubmissionFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use App\Cutup;
use Illuminate\Support\Facades\Log;

trait S3
{
    public function getTemporaryUrl($assignment_id, $file)
    {
        return \Storage::disk('s3')->temporaryUrl("assignments/$assignment_id/$file", now()->addMinutes(360));
    }

    public function fileValidator()
    {
        return ['required', 'mimes:pdf,txt,png,jpeg,jpg', 'max:500000'];//update in UploadFiles.js
    }

    public function audioFileValidator()
    {
        return ['required', 'mimes:mpga', 'max:500000'];//update in UploadFiles.js
    }


}
