<?php

namespace App\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCourse;
use Illuminate\Support\Facades\DB;
use App\Exceptions\Handler;
use \Exception;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
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
     * @param StoreCourse $request
     * @param Course $course
     */

    public function store(StoreCourse $request, Course $course)
    {
        //todo: check the validation rules
        $data = $request->validated();
        $data['user_id'] = auth()->user()->id;
        $course->create($data);
    }

    /**
     *
     * Update the specified resource in storage.
     *
     * @param StoreCourse $request
     * @param Course $course
     */
    public function update(StoreCourse $request, Course $course)
    {

        $request->validated();
        $data = $request->except('user_id');//make sure they don't do this!
        $course->update($data);
    }

    /**
     *
     * Remove the specified resource from storage.
     *
     * @param Course $course
     * @param Request $request
     * @throws Exception
     */
    public function destroy(Course $course, Request $request)
    {
        $result['success'] = false;
        try {
            $course->delete();
            $result['success'] = true;
            $result['message'] = "The course $course->name has been deleted";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $result['message'] = "There was an error removing $course->name.  Please try again or contact us for assistance.";
        }
    }

}
