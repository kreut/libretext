<?php

namespace App\Http\Controllers;

use App\Libretext;
use App\Question;
use App\Title;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Exceptions\Handler;
use \Exception;


class MindTouchEventController extends Controller
{
    public function update(Request $request, Question $Question, Libretext $libretext)
    {
      $libraries = $libretext->libraries();
        try {
            $request_host = parse_url($request->headers->get('origin'), PHP_URL_HOST);
            $request_info = [
                'host' => $request_host,
                'ip' => $request->getClientIp(),
                'url' => $request->getRequestUri(),
                'agent' => $request->header('User-Agent'),
            ];
            $request_library = str_replace('.libretexts.org', '', $request_host);

            if (!in_array($request_library, $libraries)) {
                Log::warning('access_from_unauthorized_domain_' . date('Y-m-d_H:i:s'), $request_info);
                exit;
            }

            usleep(2000000);//delay in case of race condition...want Mindtouch to update first

            $question = Question::where('page_id', $request->page_id)
                ->where('library', $request_library)
                ->first();
            if ($question) {
                if ($request->action === 'updated_question' || $request->action === 'saved') {//backward compatible
                    $Question->getQuestionIdsByPageId($request->page_id, $request_library, true);//possibly recreate non-technology piece
                } else if ($request->action === 'updated_title') {
                    $title = $libretext->getTitleByLibraryAndPageId($request_library, $request->page_id);
                    Question::where('library',$request_library)
                        ->where('page_id', $request->page_id)
                        ->update(['title' => $title]);
                } else {
                    Log::warning('unknown_request_action' . date('Y-m-d_H:i:s'), $request->action );
                }
            }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
        }

    }
}
