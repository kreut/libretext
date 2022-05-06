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

        $xml = str_replace('<prompt>', '<prompt><![CDATA[', $xml);
        $xml = str_replace('</prompt>', ']]></prompt>', $xml);
        $xml = str_replace("\n", "", $xml);
        return simplexml_load_string($xml, null, LIBXML_NOCDATA);
    }
}
