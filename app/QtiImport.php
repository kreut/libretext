<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class QtiImport extends Model
{


    public function cleanUpXml(string $xml)
    {
        $pattern = '/<simpleChoice ([^>]*)>([^<>]*)<\/simpleChoice>/i';
        $pattern = '/<simpleChoice ([^>]*)>\K.*?(?=<\/simpleChoice>)/';
        $pattern = '/<simpleChoice ([^>]*)>(.*?)<\/simpleChoice>/i';
        $xml = preg_replace_callback($pattern, function ($match) {
            Log::info(print_r($match, true));
            return '<simpleChoice ' . $match[1] . '><value>' . htmlentities($match[2]) . '</value></simpleChoice>';
        }, $xml);
       // dd($xml);
        //$xml = preg_replace('/<([^ ]+) ([^>]*)>([^<>]*)<\/\\1>/i', '<$1 $2><value>$3</value></$1>', $xml);

       // dd($xml);


        //Bug: https://stackoverflow.com/questions/8563073/disappearing-attributes-in-php-simplexml-object
        $xml = str_replace('<prompt>', '<prompt><![CDATA[', $xml);
        $xml = str_replace('</prompt>', ']]></prompt>', $xml);
        $xml = str_replace("\n", "", $xml);
        return simplexml_load_string($xml, null, LIBXML_NOCDATA);
    }
}
