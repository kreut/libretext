<?php

namespace App\Http\Controllers;

use App\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CourseAccessCode;

class EnrollmentController extends Controller
{

    public function index(Request $request)
    {

       return DB::table('courses')
           ->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
           ->join('users','courses.user_id', '=', 'users.id')
           ->where('enrollments.user_id', '=', $request->user()->id)
           ->select( DB::raw('CONCAT(first_name, " " , last_name) AS instructor'), 'courses.name', 'courses.start_date', 'courses.end_date', 'courses.id')
           ->get();

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    $data = $request->validate(['access_code' => 'exists:course_access_codes']);
    $course_id = CourseAccessCode::where('access_code', '=', $request->access_code)->first()->course_id;

    //make sure they don't sign up twice!
    $request->user()->enrollments()->attach(['course_id' =>$course_id ]);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function show(Enrollment $enrollment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function edit(Enrollment $enrollment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Enrollment $enrollment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Enrollment  $enrollment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Enrollment $enrollment)
    {
        //
    }
}
