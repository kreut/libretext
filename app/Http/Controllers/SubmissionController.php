<?php

namespace App\Http\Controllers;

use App\Exceptions\Handler;
use \Exception;
use App\Submission;
use App\Score;
use App\Assignment;
use App\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Db;

use App\Http\Requests\StoreSubmission;


class SubmissionController extends Controller
{

    public function store(StoreSubmission $request, Assignment $Assignment, Score $score)
    {
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), $Assignment, $score);

    }

    public function exploredLearningTree(Assignment $assignment, Question $question, Submission $submission)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $assignment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
//$assignment->min_time_needed_in_learning_tree
        $submission->where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->where('user_id', Auth::user()->id)
            ->update(['explored_learning_tree' => 1]);
        $response['type'] = 'success';
        $response['explored_learning_tree'] = true;
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error releasing the solutions to <strong>{$assignment->name}</strong>.  Please try again or contact us for assistance.";
        }
        return $response;
    }

}
