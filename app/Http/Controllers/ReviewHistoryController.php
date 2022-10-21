<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Exceptions\Handler;
use App\Question;
use App\ReviewHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewHistoryController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param ReviewHistory $reviewHistory
     * @return array
     * @throws Exception
     */
    public function update(Request       $request,
                           Assignment    $assignment,
                           Question      $question,
                           ReviewHistory $reviewHistory): array
    {
        try {

            $authorized = Gate::inspect('update', [$reviewHistory, $assignment]);
            if (!$authorized->allowed()) {
                $response['message'] = 'unauthorized';
                return $response;
            }
            if (!$request->reviewSessionId) {
                throw new Exception("No review session ID is present.");
            }
            $review_history = $reviewHistory->where(['session_id'=>$request->reviewSessionId])->first();
            if ($review_history){
                $review_history->updated_at = now();
                $review_history->save();
            } else {
                $reviewHistory = new ReviewHistory();
                $reviewHistory->session_id = $request->reviewSessionId;
                $reviewHistory->user_id = $request->user()->id;
                $reviewHistory->assignment_id = $assignment->id;
                $reviewHistory->question_id = $question->id;
                $reviewHistory->save();
            }
            $response['message'] = 'Review history updated.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
