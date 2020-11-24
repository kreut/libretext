<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QueryController extends Controller
{
    public function getQueryIframeSrc(Request $request, int $pageId) {
        $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();
        require_once("{$storage_path}query/{$pageId}.php");
    }
}
