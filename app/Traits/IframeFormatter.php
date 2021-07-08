<?php


namespace App\Traits;


trait IframeFormatter

{

    public function createIframeId(){
        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($permitted_chars), 0, 10);
    }

   public function formatIframeSrc($body, $id, $problemJWT = '')
   {
       preg_match('/src="([^"]+)"/', $body, $match);
       $url = $match[1] ?? '';
       if ($problemJWT) {
           if ($url) {
               $and = (substr($url, -1) === '?') ? '' : '&';//just the problemJWT or with query parameters
               $url = $url . "{$and}problemJWT=$problemJWT";
           }
       }
       return $url;
   }
}
