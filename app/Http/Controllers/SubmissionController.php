<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Score;
use App\Assignment;
use App\Question;
use Illuminate\Support\Facades\Auth;
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
        ///make sure they can actually do this!\
        //get the time they should spend in the learning tree
        //compare times
        //assuming it's good....
        Submission::where('assignment_id', $assignment->id)
            ->where('question_id', $question->id)
            ->where('user_id', Auth::user()->id)
            ->update(['explored_learning_tree' => 1]);
        $response['success'] = true;
        $response['explored_learning_tree'] = true;
        return $response;
    }

}
