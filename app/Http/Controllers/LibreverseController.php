<?php

namespace App\Http\Controllers;

use App\Libretext;
use App\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LibreverseController extends Controller
{

    public function getTitles(Request $request, Libretext $libretext){
        $titles = [];
        foreach ($request->libraries_and_page_ids as $value){
            $titles[$value['id']] = $this->getTitleByLibraryAndPageId($value['library'], $value['pageId'], $libretext);
        }
        return ['titles' =>$titles];
    }

    /**
     * @param string $library
     * @param int $pageId
     * @param Libretext $libretext
     * @return false|mixed|string
     */
    public function getTitleByLibraryAndPageId(string $library, int $pageId, Libretext $libretext)
    {
        $title_info = DB::table('titles')->where('library', $library)
            ->where('page_id', $pageId)
            ->first();
        if ($title_info) {
            return $title_info->title;
        }
        $title = $libretext->getTitleByLibraryAndPageId($library, $pageId);

        Title::create(['library' => $library,
            'page_id' => $pageId,
            'title' => $title]);
        return $title;


    }


}
