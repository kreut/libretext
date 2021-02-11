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
        $libraries = ['bio', 'biz', 'chem', 'eng', 'espanol', 'geo','human', 'k12', 'law','math','med','phys','query','socialsci','stats','workforce'];
        try {
            $request_host = parse_url($request->headers->get('origin'), PHP_URL_HOST);
            $request_info = [
                'host' => $request_host,
                'ip' => $request->getClientIp(),
                'url' => $request->getRequestUri(),
                'agent' => $request->header('User-Agent'),
            ];
            $request_library = str_replace('.libretexts.org','',$request_host);

            if (!in_array($request_library, $libraries)) {
                Log::warning('access_from_unauthorized_domain_' . date('Y-m-d_H:i:s'), $request_info);
                exit;
            }

            if ($request->action !== 'saved') {
                exit;
            }
            usleep(2000000);//delay in case of race condition...want Mindtouch to update first
            $question = Question::where('page_id', $request->page_id)
                                ->where('library',$request_library)
                                ->first();
            if ($question) {
                //Log::info("Cache busting page id $request->page_id from $request_library");
                $Question->getQuestionIdsByPageId($request->page_id, $request_library,true);//possibly recreate non-technology piece
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
