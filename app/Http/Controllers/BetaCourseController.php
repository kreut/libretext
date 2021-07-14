<?php

namespace App\Http\Controllers;

use App\BetaCourse;
use App\Course;
use App\Exceptions\Handler;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BetaCourseController extends Controller
{
    public function getBetaCoursesFromAlphaCourse(Course $alpha_course, BetaCourse $betaCourse)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('getBetaCoursesFromAlphaCourse', [$betaCourse, $alpha_course]);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $response['beta_courses'] = $alpha_course->betaCoursesInfo();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the Beta courses.  Please try again or contact us for assistance.";
        }

        return $response;
    }
}
