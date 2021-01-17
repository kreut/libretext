<?php

namespace App\Http\Controllers;

use App\CourseAccessCode;
use App\Course;
use App\Exceptions\Handler;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseAccessCodeController extends Controller
{



    /**
     * @param Request $request
     * @param CourseAccessCode $CourseAccessCode
     * @param Course $Course
     * @return array
     * @throws Exception
     */
    public function update(Request $request, CourseAccessCode $CourseAccessCode, Course $Course)
    {

        $response['type'] = 'error';
        $course = $Course->where('id', $request->course_id)->first();
        $authorized = Gate::inspect('update', [$CourseAccessCode, $course]);
        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }
        try {

            $response['type'] = 'success';
            $response['access_code'] = $CourseAccessCode->refreshCourseAccessCode($request->course_id);
            $response['message'] = 'The course access code has been refreshed.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error refreshing the course access code.  Please try again or contact us for assistance.";
        }
        return $response;


    }


}
