<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Score;
use App\Assignment;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\StoreSubmission;


class SubmissionController extends Controller
{

    public function store(StoreSubmission $request, Assignment $Assignment, Score $score)
    {
        $Submission = new Submission();
        return $Submission->store($request, new Submission(), $Assignment, $score);

    }

}
