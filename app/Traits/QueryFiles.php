<?php


namespace App\Traits;


trait QueryFiles
{

    public function getQueryIframeSrc($request, $question){
        return  $question['non_technology'] ?  "/api/get-query-iframe-src/{$question['page_id']}" : '';
        return  $question['non_technology'] ?  $request->root() . "/storage/query/{$question['page_id']}.php" : '';
    }

    public function getAppUrl(){
        //used for non-technology content.  Don't want to use localhost or you won't be able to get the assets
       return  (env('APP_ENV') === 'local') ? 'https://dev.adapt.libretexts.org' : env('APP_URL');
    }




}
