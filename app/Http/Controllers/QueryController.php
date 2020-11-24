<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class QueryController extends Controller
{
    public function getQueryIframeSrc(Request $request, int $pageId, Question $question) {

        try {
            $authorized = Gate::inspect('viewByPageId', [$question, $pageId]);
            if (!$authorized->allowed()){
                echo $authorized->message();
                exit;
            }
            $storage_path = Storage::disk('local')->getAdapter()->getPathPrefix();
            require_once("{$storage_path}query/{$pageId}.php");
        } catch (Exception $e) {
            echo "We were not able to retrieve this page: $pageId.  Please contact us for assistance.";
            $h = new Handler(app());
            $h->report($e);
        }
    }
}
