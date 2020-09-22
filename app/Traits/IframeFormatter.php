<?php


namespace App\Traits;


trait IframeFormatter

{

    public function createIframeId(){
        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($permitted_chars), 0, 10);
    }

   public function formatIframe($body, $id, $problemJWT = '')
   {


       $body = str_replace('<iframe ', "<iframe style='width: 1px;min-width: 100%;' id='$id' ", $body);

       if ($problemJWT) {
           preg_match('/src="([^"]+)"/', $body, $match);
           $url = $match[1];
           if ($url) {
               $and = (substr($url, -1) === '?') ? '' : '&';//just the problemJWT or with query parameters
               $body = str_replace($url, $url . "{$and}problemJWT=$problemJWT", $body);
           }
       }
       return $body;
   }
}
