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




}
