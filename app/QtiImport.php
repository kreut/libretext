<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QtiImport extends Model
{

    /**
     * @param string $xml
     * @return \#g#D\null|false|\SimpleXMLElement #g#D\null|false|\SimpleXMLElement
     */
    public function cleanUpXml(string $xml)
    {
        $xml = preg_replace('/<([^ ]+) ([^>]*)>([^<>]*)<\/\\1>/i', '<$1 $2><value>$3</value></$1>', $xml);
        //Bug: https://stackoverflow.com/questions/8563073/disappearing-attributes-in-php-simplexml-object
        $xml = str_replace('<prompt>', '<prompt><![CDATA[', $xml);
        $xml = str_replace('</prompt>', ']]></prompt>', $xml);
        $xml = str_replace("\n", "", $xml);
       return simplexml_load_string($xml, null, LIBXML_NOCDATA);
    }
}
