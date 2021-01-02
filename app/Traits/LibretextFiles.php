<?php


namespace App\Traits;


trait LibretextFiles
{

    public function getLocallySavedPageIframeSrc($question){
        return  $question['non_technology'] ?  "/api/get-locally-saved-page-contents/{$question['library']}/{$question['page_id']}" : '';
    }

    public function getAppUrl(){
        //used for non-technology content.  Don't want to use localhost or you won't be able to get the assets
       return  (env('APP_ENV') === 'local') ? 'https://dev.adapt.libretexts.org' : env('APP_URL');
    }




}
