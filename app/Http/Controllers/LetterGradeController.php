<?php

namespace App\Http\Controllers;

use App\Http\Requests\updateLetterGrade;
use App\LetterGrade;
use App\Course;
use Illuminate\Http\Request;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Support\Facades\Gate;


class LetterGradeController extends Controller
{
    public function update(updateLetterGrade $request, Course $course, LetterGrade $letterGrade)
    {
        $response['type'] = 'error';
        /*$authorized = Gate::inspect('updateLetterGrades', [$letterGrade, $course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }*/

        try {

            $response['type'] = 'success';
            $response['message'] = "Your letter grades have been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the letter grades.  Please try again or contact us for assistance.";
        }
        return $response;
    }
}
