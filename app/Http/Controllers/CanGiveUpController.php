<?php

namespace App\Http\Controllers;

use App\Assignment;
use App\Question;
use App\Solution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CanGiveUpController extends Controller
{
    public function validateCanGiveUp(Assignment $assignment,
                                      Question   $question,
                                      Solution   $solution)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('showSolutionByAssignmentQuestionUser', [$solution, $assignment, $question]);
        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'success';
        return $response;
    }
}
