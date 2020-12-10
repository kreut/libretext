<?php

namespace App\Http\Controllers;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Exceptions\Handler;
use \Exception;


class MindTouchEventController extends Controller
{
    public function update(Request $request, Question $Question)
    {
        try {
            if ($request->action === 'saved'){
                Log::info(print_r($request->all(), true));
            }
            return false;
            usleep(2000000);//delay in case of race condition
            $question = Question::where('page_id', $request->page_id)->first();
            if ($question) {
                $Question->getQuestionIdsByPageId($request->page_id, true);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
