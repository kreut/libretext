<?php

namespace App\Http\Controllers;

use App\Enrollment;
use App\Course;
use App\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CourseAccessCode;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{

    public function index(Request $request, Enrollment $enrollment)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('view', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
            $response['enrollments'] = DB::table('courses')
                ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
                ->join('users', 'courses.user_id', '=', 'users.id')
                ->where('enrollments.user_id', '=', $request->user()->id)
                ->select(DB::raw('CONCAT(first_name, " " , last_name) AS instructor'), 'courses.name', 'courses.start_date', 'courses.end_date', 'courses.id')
                ->get();
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error getting your enrollments.  Please try again or contact us for assistance.";
        }
        return $response;

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Enrollment $enrollment)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $enrollment);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {
        $request->validate(['access_code' => 'exists:course_access_codes']);
        $course_id = CourseAccessCode::where('access_code', '=', $request->access_code)->first()->course_id;

        //make sure they don't sign up twice!
        $response['validated'] = true;
        $course_name = Course::find($course_id)->value('name');
        if (Enrollment::where('user_id', $request->user()->id)->where('course_id', $course_id)->get()->isNotEmpty()) {
            $response['type'] = 'error';
            $response['message'] = "You are already enrolled in <strong>$course_name</strong>.";
        } else {
            $request->user()->enrollments()->attach(['course_id' => $course_id]);
            $response['type'] = 'success';
            $response['message'] = "You are now enrolled in <strong>$course_name</strong>.";
        }
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error enrolling you in the course.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Enrollment $enrollment
     * @return \Illuminate\Http\Response
     */
    public function show(Enrollment $enrollment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Enrollment $enrollment
     * @return \Illuminate\Http\Response
     */
    public function edit(Enrollment $enrollment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Enrollment $enrollment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Enrollment $enrollment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Enrollment $enrollment)
    {
        //
    }
}
