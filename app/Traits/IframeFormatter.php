<?php


namespace App\Traits;


trait iframeFormatter

{
   public function formatIframe($body, $problemJWT = '')
   {
       $id = md5(rand());//needed unqiue ids for the iframe Resizer
       $body = str_replace('<iframe ', "<iframe style='width: 1px;min-width: 100%;' id='$id' ", $body);
       if ($problemJWT) {
           preg_match('/src="([^"]+)"/', $body, $match);
           $url = $match[1];
           if ($url) {
               $body = str_replace($url, $url . "&problemJWT=$problemJWT", $body);
           }
       }
       return $body;
   }
}
