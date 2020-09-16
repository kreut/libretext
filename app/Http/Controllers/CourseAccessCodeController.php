<?php

namespace App\Http\Controllers;

use App\CourseAccessCode;
use App\Course;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

use App\Traits\AccessCodes;

class CourseAccessCodeController extends Controller
{

    use AccessCodes;

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\CourseAccessCode $courseAccessCode
     * @return \Illuminate\Http\Response
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
            $access_code = $this->createCourseAccessCode();
            DB::table('course_access_codes')
                ->where('course_id', $request->course_id)
                ->update(['access_code' => $access_code]);
            $response['type'] = 'success';
            $response['access_code'] = $access_code;
            $response['message'] = 'The course access code has been refreshed.';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error refreshing the course access code.  Please try again or contact us for assistance.";
        }
        return $response;


    }


}
