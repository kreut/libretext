<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Assignment;
use App\Question;

class AssignmentSyncQuestionController extends Controller
{

    public function store(Assignment $assignment, Question $question) {
        $assignment->questions()->syncWithoutDetaching($question);
    }
}
