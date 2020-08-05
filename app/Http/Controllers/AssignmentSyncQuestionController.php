<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Assignment;
use App\Question;

class AssignmentSyncQuestionController extends Controller
{
    public function getQuestionIdsByAssignment(Assignment $assignment) {
        return json_encode($assignment->questions()->pluck('question_id'));//need to do since it's an array
    }

    public function store(Assignment $assignment, Question $question) {
        $assignment->questions()->syncWithoutDetaching($question);
    }

    public function destroy(Assignment $assignment, Question $question) {
        $assignment->questions()->detach($question);
    }

    public function getQuestionsToView(Assignment $assignment){
       return $assignment->questions;
    }
}
