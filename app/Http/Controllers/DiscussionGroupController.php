<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\AssignmentSyncQuestion;
use App\DiscussionGroup;
use App\Exceptions\Handler;
use App\Question;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DiscussionGroupController extends Controller
{
    /**
     * @param Request $request
     * @param Assignment $assignment
     * @param Question $question
     * @param DiscussionGroup $discussionGroup
     * @return array
     * @throws Exception
     */
    public function getByAssignmentQuestionUser(Request         $request,
                                                Assignment      $assignment,
                                                Question        $question,
                                                DiscussionGroup $discussionGroup)
    {

        try {
            $response['type'] = 'error';
            $authorized = Gate::inspect('getByAssignmentQuestionUser', [$discussionGroup, $assignment]);

            if (!$authorized->allowed()) {
                $response['message'] = $authorized->message();
                return $response;
            }
            $group = $discussionGroup->store($assignment->id, $question->id, $request->user()->id);

            $response['type'] = 'success';
            $response['group'] = $group;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your discussion group.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
