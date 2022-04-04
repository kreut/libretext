<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\ShownHint;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShownHintController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param ShownHint $shownHint
     * @return array
     * @throws Exception
     */
    public function store(Request    $request,
                          Assignment $assignment,
                          Question   $question,
                          ShownHint  $shownHint): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('store', [$shownHint, $assignment::find($assignment->id), $question->id]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }


        try {
            $shownHint->user_id = $request->user()->id;
            $shownHint->assignment_id = $assignment->id;
            $shownHint->question_id = $question->id;
            $shownHint->save();
            $response['hint'] = $question->hint;
            $response['type'] = 'success';
        } catch (Exception $e) {
            $response['message'] = 'We were unable to confirm that you would like the hint to be shown.';
            $h = new Handler(app());
            $h->report($e);
        }
        return $response;


    }

}
