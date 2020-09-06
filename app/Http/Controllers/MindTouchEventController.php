<?php

namespace App\Http\Controllers;


use App\Query;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\LOG;

use App\Exceptions\Handler;
use \Exception;


class MindTouchEventController extends Controller
{
    public function update(Request $request, Query $Query)
    {
        LOG::info($request->all());
        $Query->updatePageInfoByPageId($request->page_id);
    }
}
