<?php


namespace App\Traits;


trait LibretextFiles
{
    /**
     * @param $question
     * @return string
     */
    public function getHeaderHtmlIframeSrc($question): string
    {
        return  $question['non_technology'] ?  "/api/get-header-html/{$question['id']}" : '';
    }

    public function getAppUrl(){
        //used for non-technology content.  Don't want to use localhost or you won't be able to get the assets
       return  (app()->environment('local')) ? 'https://dev.adapt.libretexts.org' : config('app.url');
    }




}
