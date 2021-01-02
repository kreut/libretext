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
            $request_host = parse_url($request->headers->get('origin'), PHP_URL_HOST);
            $request_info = [
                'host' => $request_host,
                'ip' => $request->getClientIp(),
                'url' => $request->getRequestUri(),
                'agent' => $request->header('User-Agent'),
            ];
            if ($request_host !== 'query.libretexts.org') {
                Log::warning('access_from_unauthorized_domain_' . date('Y-m-d_H:i:s'), $request_info);
                exit;
            }

            if ($request->action !== 'saved') {
                exit;
            }
            Log::info(print_r($request->all(), true));

            usleep(2000000);//delay in case of race condition
            $question = Question::where('page_id', $request->page_id)->first();
            if ($question) {
                $Question->getQuestionIdsByPageId($request->page_id, 'query',true);
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
