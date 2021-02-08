<?php

namespace App\Http\Controllers;

use App\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LibreverseController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param \App\LearningObjective $learningObjective
     * @return \Illuminate\Http\Response
     */
    public function getStudentLearningObjectiveByLibraryAndPageId(Request $request, string $library, int $pageId)
    {
        $response = Http::get("https://{$library}.libretexts.org/@api/deki/pages/{$pageId}/contents");


        $xml = simplexml_load_string($response->body());
        return $xml->body[0];

    }

    public function getTitles(Request $request){
        $titles = [];
        foreach ($request->libraries_and_page_ids as $value){
            $titles[$value['id']] = $this->getTitleByLibraryAndPageId($value['library'], $value['pageId']);
        }
        return ['titles' =>$titles];
    }
    /**
     * @param Request $request
     * @param string $library
     * @param int $pageId
     */
    public function getTitleByLibraryAndPageId(string $library, int $pageId)
    {
        $title_info = DB::table('titles')->where('library', $library)
            ->where('page_id', $pageId)
            ->first();
        if ($title_info) {
            return $title_info->title;
        }

        $response = Http::get("https://{$library}.libretexts.org/@api/deki/pages/{$pageId}/contents");
        $xml = simplexml_load_string($response->body());
        if (($pos = strpos($xml->attributes()->title[0], ":")) !== FALSE) {
            $title = substr($xml->attributes()->title[0], $pos + 1);
        } else {
            $title = $xml->attributes()->title[0];
        }
        Title::create(['library' => $library,
            'page_id' => $pageId,
            'title' => $title]);
        return $title;


    }


}
