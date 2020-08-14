<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LearningObjectiveController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $library, int $pageId)
    {
        $response = Http::get("https://{$library}.libretexts.org/@api/deki/pages/{$pageId}/contents");


   $xml = simplexml_load_string($response->body());
 return $xml->body[0];

    }

    /**
     * @param Request $request
     * @param string $library
     * @param int $pageId
     */
    public function getTitle(Request $request, string $library, int $pageId)
    {
        $response = Http::get("https://{$library}.libretexts.org/@api/deki/pages/{$pageId}/contents");


        $xml = simplexml_load_string($response->body());
        if (($pos = strpos($xml->attributes()->title[0], ":")) !== FALSE) {
            return substr($xml->attributes()->title[0], $pos+1);
        }
        return $xml->attributes()->title[0];


    }




}
