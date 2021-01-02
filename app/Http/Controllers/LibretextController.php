<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LibretextController extends Controller
{
    /**
     * @param int $pageId
     * @param Question $question
     * @return array|string[]
     * @throws Exception
     */
    public function getLocallySavedPageContents(string $library, int $pageId, Question $question) {

        try {
            $authorized = Gate::inspect('viewByPageId', [$question, $pageId]);
            if (!$authorized->allowed()){
                if (env('DB_DATABASE') === "test_libretext"){
                    return ['message' => $authorized->message()];
                }
                echo $authorized->message();
            } else {
                if (env('DB_DATABASE') === "test_libretext"){
                    return ['message' => 'authorized'];
                }
                $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();
                require_once("{$storage_path}{$library}/{$pageId}.php");
            }
        } catch (Exception $e) {
            echo "We were not able to retrieve Page Id $pageId from the $library library.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
