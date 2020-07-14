<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Assignment;
use App\Question;

class AssignmentSyncQuestionController extends Controller
{
    public function index(Assignment $assignment) {
        return json_encode($assignment->questions()->pluck('question_id'));
    }

    public function store(Assignment $assignment, Question $question) {
        $assignment->questions()->syncWithoutDetaching($question);
    }
}
