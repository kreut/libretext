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

    public function getTemporaryUrlForNonTechnologyIframeSrc($question){
        return  $question['non_technology'] ? Storage::disk('s3')->temporaryUrl("query/{$question['page_id']}.html", now()->addMinutes(360)) : '';
    }

    public function getAppUrl(){
        //used for non-technology content.  Don't want to use localhost or you won't be able to get the assets
       return  (env('APP_ENV') === 'local') ? 'https://dev.adapt.libretexts.org' : env('APP_ENV');
    }




}
