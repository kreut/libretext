<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\DataShop;
use App\Exceptions\Handler;
use App\Question;
use App\ReviewHistory;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
            $response['type'] = 'errors';
            if (!$authorized->allowed()) {
                $response['message'] = 'unauthorized';
                return $response;
            }
            if (!$request->reviewSessionId) {
                throw new Exception("No review session ID is present.");
            }

            $review_history = $reviewHistory->where('session_id',$request->reviewSessionId)->first();
            if ($review_history){
                $reviewHistory->updated_at = $request->end;
                $review_history->save();
            } else {
                $reviewHistory = new ReviewHistory();
                $reviewHistory->session_id = $request->reviewSessionId;
                $reviewHistory->user_id = $request->user()->id;
                $reviewHistory->assignment_id = $assignment->id;
                $reviewHistory->question_id = $question->id;
                $reviewHistory->created_at = $request->start;
                $reviewHistory->updated_at = $request->end;
                $reviewHistory->save();
            }

            $dataShop = new DataShop();
            $assignment_question = DB::table('assignment_question')
                ->where('assignment_id', $assignment->id)
                ->where('question_id', $question->id)
                ->first();

            $reviewHistory->question_id = $question->id;
            $reviewHistory->email = auth()->user()->email;
            $dataShop->store('time_to_review', $reviewHistory, $assignment, $assignment_question);
            $response['message'] = 'Review history updated.';
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = $e->getMessage();
        }
        return $response;
    }
}
