<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCourse;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //todo it gets courses in descending order
        return response(DB::table('courses')
            ->where('user_id', auth()->user()->id)
            ->orderBy('start_date', 'desc')
            ->get());
    }

    /**
     *
     * Store a newly created resource in storage.
     *
     *
     * @param StoreCourse $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */

    public function store(StoreCourse $request)
    {
        //todo: check the validation rules
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        return response(Course::create($data));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Course $course
     * @return \Illuminate\Http\Response
     */
    public function show(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreCourse $request
     * @param Course $course
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(StoreCourse $request, Course $course)
    {

        $request->validated();
        $data = $request->except('user_id');//make sure they don't do this!
        return response($course->update($data));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Course $course
     * @return \Illuminate\Http\Response
     */
    public function destroy(Course $course)
    {
        //
    }
}
