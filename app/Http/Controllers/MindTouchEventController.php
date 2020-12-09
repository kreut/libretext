<?php

namespace App\Http\Controllers;
use App\Question;
use Illuminate\Http\Request;


use App\Exceptions\Handler;
use \Exception;


class MindTouchEventController extends Controller
{
    public function update(Request $request, Question $Question)
    {
        try {
            usleep(2000000);//delay in case of race condition
            $Question->getQuestionIdsByPageId($request->page_id, true);
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
